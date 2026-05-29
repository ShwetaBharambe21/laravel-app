provider "aws" {
  region = var.aws_region
}

# ─────────────────────────────────────────────
# VPC & Networking
# ─────────────────────────────────────────────
module "vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "~> 5.0"

  name = "laravel-vpc"
  cidr = "10.0.0.0/16"

  azs             = ["${var.aws_region}a", "${var.aws_region}b"]
  public_subnets  = ["10.0.1.0/24", "10.0.2.0/24"]
  private_subnets = ["10.0.11.0/24", "10.0.12.0/24"]

  enable_nat_gateway = true
  single_nat_gateway = true

  tags = {
    Project = "laravel-app"
  }
}

# ─────────────────────────────────────────────
# ECR Repository
# ─────────────────────────────────────────────
resource "aws_ecr_repository" "laravel" {
  name                 = "laravel-app"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = {
    Name = "laravel-app"
  }
}

# ─────────────────────────────────────────────
# Security Group: ALB (public internet access)
# ─────────────────────────────────────────────
resource "aws_security_group" "alb" {
  name        = "laravel-alb-sg"
  description = "Allow HTTP and HTTPS from internet"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "laravel-alb-sg"
  }
}

# ─────────────────────────────────────────────
# Security Group: EC2 (only ALB can reach it)
# ─────────────────────────────────────────────
resource "aws_security_group" "ec2" {
  name        = "laravel-ec2-sg"
  description = "Allow traffic only from ALB"
  vpc_id      = module.vpc.vpc_id

  ingress {
    description     = "HTTP from ALB"
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "laravel-ec2-sg"
  }
}

# ─────────────────────────────────────────────
# IAM Role for EC2
# (ECR pull + SSM access + CloudWatch logs)
# ─────────────────────────────────────────────
resource "aws_iam_role" "ec2_role" {
  name = "laravel-ec2-role"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Action    = "sts:AssumeRole"
        Effect    = "Allow"
        Principal = {
          Service = "ec2.amazonaws.com"
        }
      }
    ]
  })
}

resource "aws_iam_role_policy_attachment" "ssm" {
  role       = aws_iam_role.ec2_role.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_role_policy_attachment" "ecr" {
  role       = aws_iam_role.ec2_role.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonEC2ContainerRegistryReadOnly"
}

resource "aws_iam_role_policy_attachment" "cloudwatch" {
  role       = aws_iam_role.ec2_role.name
  policy_arn = "arn:aws:iam::aws:policy/CloudWatchAgentServerPolicy"
}

resource "aws_iam_instance_profile" "ec2" {
  name = "laravel-ec2-profile"
  role = aws_iam_role.ec2_role.name
}

# ─────────────────────────────────────────────
# Application Load Balancer
# ─────────────────────────────────────────────
resource "aws_lb" "main" {
  name               = "laravel-alb"
  load_balancer_type = "application"
  subnets            = module.vpc.public_subnets
  security_groups    = [aws_security_group.alb.id]

  tags = {
    Name = "laravel-alb"
  }
}

# ─────────────────────────────────────────────
# Target Groups (Blue/Green)
# ─────────────────────────────────────────────
resource "aws_lb_target_group" "blue" {
  name     = "laravel-blue-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = module.vpc.vpc_id

  health_check {
    path                = "/health"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    interval            = 30
    timeout             = 10
    matcher             = "200"
  }

  tags = {
    Name = "laravel-blue-tg"
  }
}

resource "aws_lb_target_group" "green" {
  name     = "laravel-green-tg"
  port     = 80
  protocol = "HTTP"
  vpc_id   = module.vpc.vpc_id

  health_check {
    path                = "/health"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    interval            = 30
    timeout             = 10
    matcher             = "200"
  }

  tags = {
    Name = "laravel-green-tg"
  }
}

# ─────────────────────────────────────────────
# ALB Listener (HTTP → Blue target group)
# ─────────────────────────────────────────────
resource "aws_lb_listener" "http" {
  load_balancer_arn = aws_lb.main.arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.blue.arn
  }
}

# ─────────────────────────────────────────────
# Launch Template
# ─────────────────────────────────────────────
resource "aws_launch_template" "laravel" {
  name_prefix   = "laravel-"
  image_id      = var.ami_id
  instance_type = var.instance_type

  iam_instance_profile {
    name = aws_iam_instance_profile.ec2.name
  }

  vpc_security_group_ids = [aws_security_group.ec2.id]

  user_data = base64encode(file("${path.module}/user_data.sh"))

  tag_specifications {
    resource_type = "instance"
    tags = {
      Name = "laravel-app"
    }
  }

  lifecycle {
    create_before_destroy = true
  }
}

# ─────────────────────────────────────────────
# Auto Scaling Group
# ─────────────────────────────────────────────
resource "aws_autoscaling_group" "laravel" {
  name                      = "laravel-asg"
  min_size                  = 2
  max_size                  = 6
  desired_capacity          = 2
  vpc_zone_identifier       = module.vpc.private_subnets
  target_group_arns         = [aws_lb_target_group.blue.arn]
  health_check_type         = "ELB"
  health_check_grace_period = 300

  launch_template {
    id      = aws_launch_template.laravel.id
    version = "$Latest"
  }

  instance_refresh {
    strategy = "Rolling"
    preferences {
      min_healthy_percentage = 100
      instance_warmup        = 120
    }
  }

  tag {
    key                 = "Name"
    value               = "laravel-app"
    propagate_at_launch = true
  }
}

# ─────────────────────────────────────────────
# Auto Scaling Policy (CPU-based)
# ─────────────────────────────────────────────
resource "aws_autoscaling_policy" "scale_cpu" {
  name                   = "laravel-cpu-scaling"
  autoscaling_group_name = aws_autoscaling_group.laravel.name
  policy_type            = "TargetTrackingScaling"

  target_tracking_configuration {
    predefined_metric_specification {
      predefined_metric_type = "ASGAverageCPUUtilization"
    }
    target_value = 70.0
  }
}

# ─────────────────────────────────────────────
# CloudWatch Log Groups
# ─────────────────────────────────────────────
resource "aws_cloudwatch_log_group" "app" {
  name              = "/laravel/app"
  retention_in_days = 30
}

resource "aws_cloudwatch_log_group" "nginx" {
  name              = "/laravel/nginx"
  retention_in_days = 30
}

resource "aws_cloudwatch_log_group" "worker" {
  name              = "/laravel/worker"
  retention_in_days = 30
}

# ─────────────────────────────────────────────
# CloudWatch Alarm: Unhealthy Hosts
# ─────────────────────────────────────────────
resource "aws_cloudwatch_metric_alarm" "unhealthy_hosts" {
  alarm_name          = "laravel-unhealthy-hosts"
  comparison_operator = "GreaterThanOrEqualToThreshold"
  evaluation_periods  = 2
  metric_name         = "UnHealthyHostCount"
  namespace           = "AWS/ApplicationELB"
  period              = 60
  statistic           = "Average"
  threshold           = 1
  alarm_description   = "Fires when ALB detects unhealthy EC2 targets"

  dimensions = {
    LoadBalancer = aws_lb.main.arn_suffix
    TargetGroup  = aws_lb_target_group.blue.arn_suffix
  }
}
