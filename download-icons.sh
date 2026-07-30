#!/bin/bash

# Maintenance script — refetches the backend icons from Tabler Icons (MIT).
# Not part of the distribution package: excluded through .gitattributes.
#
# The icons are committed to the repository, so this script only needs running
# when an icon is added or has to be refreshed. Run it from the extension root.
#
# ext_icon.svg and Resources/Public/Icons/Extension.svg are the same Tabler
# artwork and are not refetched here.

ICONS_DIR="Resources/Public/Icons"
BASE_URL="https://cdn.jsdelivr.net/npm/@tabler/icons@latest/icons"

echo "Downloading Tabler Icons to $ICONS_DIR..."

mkdir -p "$ICONS_DIR"
cd "$ICONS_DIR" || exit

# Plugin icon
curl -sL "$BASE_URL/route.svg" -o route.svg

# Questionnaire
curl -sL "$BASE_URL/clipboard-list.svg" -o clipboard-list.svg

# Question types
curl -sL "$BASE_URL/help-circle.svg" -o help-circle.svg
curl -sL "$BASE_URL/circle-dot.svg" -o circle-dot.svg
curl -sL "$BASE_URL/checkbox.svg" -o checkbox.svg
curl -sL "$BASE_URL/toggle-left.svg" -o toggle-left.svg
curl -sL "$BASE_URL/adjustments-horizontal.svg" -o adjustments-horizontal.svg
curl -sL "$BASE_URL/info-circle.svg" -o info-circle.svg

# Answer Option
curl -sL "$BASE_URL/list-check.svg" -o list-check.svg

# Condition
curl -sL "$BASE_URL/git-branch.svg" -o git-branch.svg

# Result Page
curl -sL "$BASE_URL/flag-check.svg" -o flag-check.svg

# Advice Block
curl -sL "$BASE_URL/bulb.svg" -o bulb.svg
curl -sL "$BASE_URL/calculator.svg" -o calculator.svg
curl -sL "$BASE_URL/message-check.svg" -o message-check.svg

# Saved result
curl -sL "$BASE_URL/device-floppy.svg" -o device-floppy.svg

echo ""
echo "Verifying downloaded files..."
for file in *.svg; do
    if [ -f "$file" ]; then
        size=$(wc -c < "$file")
        if [ "$size" -lt 100 ]; then
            echo "⚠️  $file seems too small (${size} bytes) - might be an error"
        else
            echo "✓ $file (${size} bytes)"
        fi
    fi
done

echo ""
echo "✓ Done! Downloaded $(ls -1 *.svg 2>/dev/null | wc -l | xargs) icons to $ICONS_DIR"
echo "Run 'ddev typo3 cache:flush' to apply the icons in the TYPO3 backend."

