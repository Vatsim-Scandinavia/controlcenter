#!/bin/bash

echo "Starting theme building process..."

# Install
apt update
apt install -y ca-certificates curl gnupg
mkdir -p /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg

NODE_MAJOR=22
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list

apt update
apt install nodejs -y

# Build
npm ci --omit dev
npm config set cache /tmp --global
su www-data -s /usr/bin/npm run build

# Cleanup
npm cache clean --force
apt purge curl gnupg nodejs -y
apt autoremove -y
rm -r /etc/apt/sources.list.d/nodesource.list
rm -r /etc/apt/keyrings/nodesource.gpg

rm -rf /app/node_modules/

echo "Theme building process complete. Cleaned up all dependecies to save space."