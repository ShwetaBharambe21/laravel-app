#!/bin/bash
echo "🔄 Rolling back to previous image..."

PREV_TAG=$(aws ssm get-parameter \
  --name "/laravel/deploy/previous-tag" \
  --query Parameter.Value --output text)

if [ "$PREV_TAG" = "none" ]; then
  echo "No previous version to rollback to"; exit 1
fi

# Update the current tag back to previous
aws ssm put-parameter \
  --name "/laravel/deploy/image-tag" \
  --value "$PREV_TAG" \
  --type String --overwrite

# Trigger a new instance refresh with the old image
aws autoscaling start-instance-refresh \
  --auto-scaling-group-name laravel-asg \
  --strategy Rolling \
  --preferences '{"MinHealthyPercentage":100,"InstanceWarmup":60}'

echo "✅ Rollback initiated with tag: $PREV_TAG"
