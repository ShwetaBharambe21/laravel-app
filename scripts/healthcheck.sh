#!/bin/bash
REFRESH_ID=$1
echo "Waiting for Instance Refresh: $REFRESH_ID"

for i in {1..40}; do
  STATUS=$(aws autoscaling describe-instance-refreshes \
    --auto-scaling-group-name laravel-asg \
    --instance-refresh-ids "$REFRESH_ID" \
    --query 'InstanceRefreshes[0].Status' --output text)
  
  echo "[$i/40] Status: $STATUS"
  
  case $STATUS in
    Successful) echo "✅ Deploy complete"; exit 0 ;;
    Failed|Cancelled) echo "❌ Instance Refresh failed"; exit 1 ;;
    *) sleep 30 ;;
  esac
done

echo "⏰ Timed out waiting for refresh"; exit 1