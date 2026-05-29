variable "aws_region" {
  description = "AWS region to deploy into"
  type        = string
  default     = "us-east-1"
}

variable "ami_id" {
  description = "Amazon Linux 2023 AMI ID (update if using a different region)"
  type        = string
  # Amazon Linux 2023 AMI for us-east-1
  # To find the latest: aws ec2 describe-images --owners amazon \
  #   --filters "Name=name,Values=al2023-ami-*-x86_64" \
  #   --query "sort_by(Images,&CreationDate)[-1].ImageId" --output text
  default = "ami-0c02fb55956c7d316"
}

variable "instance_type" {
  description = "EC2 instance type"
  type        = string
  default     = "t3.small"
}

variable "app_name" {
  description = "Application name used for tagging"
  type        = string
  default     = "laravel-app"
}
