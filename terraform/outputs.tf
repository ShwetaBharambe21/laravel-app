output "alb_dns_name" {
  description = "Public DNS of the Application Load Balancer — use this as your app URL"
  value       = aws_lb.main.dns_name
}

output "ecr_repository_url" {
  description = "ECR URL to push Docker images to"
  value       = aws_ecr_repository.laravel.repository_url
}

output "asg_name" {
  description = "Auto Scaling Group name (used in deploy scripts)"
  value       = aws_autoscaling_group.laravel.name
}

output "vpc_id" {
  description = "VPC ID"
  value       = module.vpc.vpc_id
}

output "private_subnet_ids" {
  description = "Private subnet IDs (EC2 instances live here)"
  value       = module.vpc.private_subnets
}

output "public_subnet_ids" {
  description = "Public subnet IDs (ALB lives here)"
  value       = module.vpc.public_subnets
}
