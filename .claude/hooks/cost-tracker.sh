#!/bin/bash
# cost-tracker.sh
# Logs session metadata to ~/.claude/session-costs.log on every Stop event.
# Outputs a systemMessage so the summary appears in the UI.

INPUT=$(cat)
SESSION_ID=$(echo "$INPUT" | jq -r '.session_id // "unknown"')
STOP_REASON=$(echo "$INPUT" | jq -r '.stop_reason // "unknown"')
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
LOG_FILE="$HOME/.claude/session-costs.log"

# Append session entry to log
echo "[$TIMESTAMP] session=$SESSION_ID reason=$STOP_REASON" >> "$LOG_FILE"

# Count total sessions logged
TOTAL=$(wc -l < "$LOG_FILE" | tr -d ' ')

# Show summary in UI
echo "{\"systemMessage\": \"Session ended ($STOP_REASON). Session ID: $SESSION_ID\\nLogged to: $LOG_FILE (total sessions: $TOTAL)\\nCheck real token costs at: console.anthropic.com\"}"
