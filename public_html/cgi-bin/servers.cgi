#!/bin/bash
# SBC Web UI dashboard generator
# Checks localhost and LAN IP, generates clickable HTML links

# Ports to check
PORTS=(8080 9090 10000 3000)

# Output file
OUTFILE="dashboard.html"

# Detect LAN IP (first non-loopback IPv4)
LAN_IP=$(hostname -I | awk '{print $1}')
LOCAL_IP="127.0.0.1"

# Start HTML
cat <<EOF > "$OUTFILE"
<!DOCTYPE html>
<html>
<head>
<meta charset='utf-8'>
<title>SBC Web UIs</title>
<style>
body { font-family: system-ui; padding: 1rem; max-width: 900px; margin: auto; }
ul { list-style: none; padding: 0; }
li { margin-bottom: 0.5rem; }
a { text-decoration: none; color: #0066cc; }
</style>
</head>
<body>
<h1>Available Web UIs</h1>
<ul>
EOF

for PORT in "${PORTS[@]}"; do
    # Check localhost first
    if timeout 1 bash -c "echo > /dev/tcp/$LOCAL_IP/$PORT" &>/dev/null; then
        echo "<li><strong>Local:</strong> <a href='http://$LOCAL_IP:$PORT/' target='_blank'>Port $PORT</a></li>" >> "$OUTFILE"
    fi
    # Check LAN IP (only if different from localhost)
    if [[ "$LAN_IP" != "$LOCAL_IP" ]]; then
        if timeout 1 bash -c "echo > /dev/tcp/$LAN_IP/$PORT" &>/dev/null; then
            echo "<li><strong>Network:</strong> <a href='http://$LAN_IP:$PORT/' target='_blank'>Port $PORT</a></li>" >> "$OUTFILE"
        fi
    fi
done

# End HTML
cat <<EOF >> "$OUTFILE"
</ul>
</body>
</html>
EOF

echo "Dashboard generated: $OUTFILE"
