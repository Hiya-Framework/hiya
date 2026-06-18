#!/bin/sh

# Massive Queue Test Script
# Usage: ./massive_test.sh [number_of_jobs] [queue_name]

# Default values
NUM_JOBS=${1:-100}
QUEUE_NAME=${2:-default}
SLEEP_TIME=1

echo "=========================================="
echo "     Massive Queue Test"
echo "=========================================="
echo "Jobs to push: $NUM_JOBS"
echo "Queue: $QUEUE_NAME"
echo "=========================================="
echo ""


# Reset queue sebelum test
echo ">>> Resetting queue..."
php hiyac.php queue reset

# Record start time
START_TIME=$(date +%s)

# Push massive jobs
echo ""
echo ">>> Pushing $NUM_JOBS jobs to queue..."
echo "Progress: "

for i in $(seq 1 $NUM_JOBS)
do
    # Show progress every 10 jobs
    if [ $((i % 10)) -eq 0 ]; then
        echo -n " $i"
    fi
    
    # Push job dengan data bervariasi
    php hiyac.php queue push TestJob "{\"job_id\":$i,\"message\":\"Test job #$i\",\"timestamp\":$(date +%s)}" --queue=$QUEUE_NAME > /dev/null 2>&1
done

echo ""
echo ""
END_PUSH_TIME=$(date +%s)
PUSH_DURATION=$((END_PUSH_TIME - START_TIME))
echo "Push completed in ${PUSH_DURATION}s (avg: $(echo "scale=2; $NUM_JOBS / $PUSH_DURATION" | bc) jobs/s)"

# Check stats after push
echo ""
echo ">>> Stats after push:"
php hiyac.php queue stats

# Process all jobs
echo ""
echo ">>> Processing all jobs..."
PROCESS_START=$(date +%s)

# Process jobs until queue empty
PROCESSED=0
while true; do
    OUTPUT=$(php hiyac.php queue work --once 2>&1)
    if echo "$OUTPUT" | grep -q "Processed 0 jobs"; then
        break
    fi
    PROCESSED=$((PROCESSED + 1))
    
    # Show progress every 10 jobs
    if [ $((PROCESSED % 10)) -eq 0 ]; then
        echo -n " $PROCESSED"
    fi
done

echo ""
END_PROCESS_TIME=$(date +%s)
PROCESS_DURATION=$((END_PROCESS_TIME - PROCESS_START))
echo "Processing completed in ${PROCESS_DURATION}s (avg: $(echo "scale=2; $PROCESSED / $PROCESS_DURATION" | bc) jobs/s)"

# Final stats
echo ""
echo ">>> Final stats:"
php hiyac.php queue stats

# Summary
echo ""
echo "=========================================="
echo "     Test Summary"
echo "=========================================="
echo "Jobs pushed: $NUM_JOBS"
echo "Jobs processed: $PROCESSED"
echo "Push time: ${PUSH_DURATION}s"
echo "Process time: ${PROCESS_DURATION}s"
echo "Total time: $((END_PROCESS_TIME - START_TIME))s"
echo "=========================================="
