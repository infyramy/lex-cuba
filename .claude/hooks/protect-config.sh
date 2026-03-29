#!/bin/bash
# protect-config.sh
# Blocks edits to linter/build/test config files to prevent weakening them.
# Fires on PreToolUse for Edit and Write tools.

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

if [[ -z "$FILE_PATH" ]]; then
  exit 0
fi

# Patterns that match protected config filenames
PROTECTED_PATTERNS=(
  "eslint"
  ".eslintrc"
  "prettier"
  ".prettierrc"
  "tsconfig"
  "phpunit.xml"
  "phpstan.neon"
  "phpstan.dist.neon"
  "vite.config"
  "tailwind.config"
  "jest.config"
  ".babelrc"
  "webpack.config"
  ".stylelintrc"
  "stylelint.config"
)

BASENAME=$(basename "$FILE_PATH")

for pattern in "${PROTECTED_PATTERNS[@]}"; do
  if [[ "$BASENAME" == *"$pattern"* ]]; then
    echo "{\"hookSpecificOutput\": {\"hookEventName\": \"PreToolUse\", \"permissionDecision\": \"ask\", \"permissionDecisionReason\": \"protect-config: '$BASENAME' is a protected linter/build config. Confirm this change is intentional and does NOT weaken any rules.\"}}"
    exit 0
  fi
done

exit 0
