#!/bin/bash
set -eo pipefail

# ─────────────────────────────────────────────
# user_data.sh — runs on every new EC2 instance
# All config is read from instance metadata and SSM at runtime.
# ─────────────────────────────────────────────

# Install Docker and CloudWatch agent
yum update -y
yum install -y docker amazon-cloudwatch-agent

# Start Docker
systemctl enable docker
systemctl start docker

usermod -aG docker ec2-user

# ── Resolve region from instance metadata ─────
AWS_REGION=$(curl -s http://169.254.169.254/latest/meta-data/placement/region)

# ── Resolve ECR repo URL from SSM ─────────────
ECR_REPO=$(aws ssm get-parameter \
  --region "$AWS_REGION" \
  --name "/laravel/config/ecr-repo" \
  --query "Parameter.Value" --output text)

ECR_REGISTRY=$(echo "$ECR_REPO" | cut -d'/' -f1)

# ── Authenticate to ECR ───────────────────────
aws ecr get-login-password --region "$AWS_REGION" | \
  docker login --username AWS --password-stdin "$ECR_REGISTRY"

# ── Pull image tag from SSM (set by GitHub Actions) ──
IMAGE_TAG=$(aws ssm get-parameter \
  --region "$AWS_REGION" \
  --name "/laravel/deploy/image-tag" \
  --query "Parameter.Value" \
  --output text 2>/dev/null || echo "latest")

IMAGE="$ECR_REPO:$IMAGE_TAG"
echo "Pulling image: $IMAGE"
docker pull "$IMAGE"

# ── Pull env file from SSM Parameter Store ────
mkdir -p /etc/laravel
if aws ssm get-parameter \
  --region "$AWS_REGION" \
  --name "/laravel/env/APP_ENV_FILE" \
  --with-decryption \
  --query "Parameter.Value" \
  --output text 2>/dev/null > /etc/laravel/.env; then
  echo "Loaded .env from SSM"
else
  echo "WARNING: SSM param /laravel/env/APP_ENV_FILE not found — using empty .env"
  touch /etc/laravel/.env
fi

# ── Run the Laravel container ─────────────────
INSTANCE_ID=$(curl -s http://169.254.169.254/latest/meta-data/instance-id)

docker run -d \
  --name laravel-app \
  --restart unless-stopped \
  -p 80:80 \
  --env-file /etc/laravel/.env \
  --log-driver=awslogs \
  --log-opt awslogs-region="$AWS_REGION" \
  --log-opt awslogs-group=/laravel/app \
  --log-opt awslogs-create-group=true \
  --log-opt awslogs-stream="$INSTANCE_ID" \
  "$IMAGE"

# ── Wait for container to be healthy ─────────
echo "Waiting for app to be healthy..."
for i in $(seq 1 18); do
  STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health || echo "000")
  if [ "$STATUS" = "200" ]; then
    echo "App is healthy!"
    break
  fi
  echo "Attempt $i: HTTP $STATUS — retrying in 10s..."
  sleep 10
done

# ── Start CloudWatch Agent ────────────────────
/opt/aws/amazon-cloudwatch-agent/bin/amazon-cloudwatch-agent-ctl \
  -a fetch-config \
  -m ec2 \
  -s \
  -c ssm:/laravel/cloudwatch-config 2>/dev/null || true

echo "Bootstrap complete."
