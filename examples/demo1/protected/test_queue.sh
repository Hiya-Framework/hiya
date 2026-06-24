#!/bin/bash

# Hiya Queue System Test Script
# Usage: ./test_queue.sh

echo "=========================================="
echo "     Hiya Queue System Test Suite"
echo "=========================================="
echo ""


echo "1. Testing Queue Help"
echo "------------------------------------------"
php hiyac.php queue help
echo ""

echo "2. Testing Queue Info"
echo "------------------------------------------"
php hiyac.php queue info
echo ""

echo "3. Testing Queue Stats (Before)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "4. Testing Queue Reset (Clear all jobs)"
echo "------------------------------------------"
php hiyac.php queue reset
echo ""

echo "5. Testing Queue Stats (After Reset)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "6. Testing Push Test Job"
echo "------------------------------------------"
php hiyac.php queue push TestJob '{"message":"Hello from test script"}'
echo ""

echo "7. Testing Queue Stats (After Push)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "8. Testing Queue Work (Process one job)"
echo "------------------------------------------"
php hiyac.php queue work --once
echo ""

echo "9. Testing Queue Stats (After Process)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "10. Testing Push Multiple Jobs"
echo "------------------------------------------"
php hiyac.php queue push TestJob '{"message":"Job 1"}' --priority=high
php hiyac.php queue push TestJob '{"message":"Job 2"}' --priority=medium
php hiyac.php queue push TestJob '{"message":"Job 3"}' --priority=low
php hiyac.php queue push TestJob '{"message":"Delayed Job"}' --delay=5
echo ""

echo "11. Testing Queue Stats (Multiple Jobs)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "12. Testing Queue Work (Process all pending)"
echo "------------------------------------------"
php hiyac.php queue work --once
php hiyac.php queue work --once
php hiyac.php queue work --once
echo ""

echo "13. Testing Queue Stats (After Processing)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "14. Testing Push High Priority Job"
echo "------------------------------------------"
php hiyac.php queue push TestJob '{"message":"HIGH PRIORITY JOB"}' --priority=high
echo ""

echo "15. Testing Push Low Priority Job"
echo "------------------------------------------"
php hiyac.php queue push TestJob '{"message":"LOW PRIORITY JOB"}' --priority=low
echo ""

echo "16. Testing Queue Stats (Priority Jobs)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "17. Testing Queue Clear Completed Jobs"
echo "------------------------------------------"
php hiyac.php queue clear completed
echo ""

echo "18. Testing Queue Stats (After Clear)"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "19. Testing Queue Retry (if any failed jobs)"
echo "------------------------------------------"
php hiyac.php queue retry
echo ""

echo "20. Testing Queue Final Stats"
echo "------------------------------------------"
php hiyac.php queue stats
echo ""

echo "=========================================="
echo "     Test Complete!"
echo "=========================================="
