module.exports = {
  apps: [
    {
      name: 'eudr-frontend',
      cwd: '/srv/eudr/frontend',
      script: 'pnpm',
      args: 'start',
      interpreter: 'none',
      env: { NODE_ENV: 'production', PORT: 3000 },
      autorestart: true,
      watch: false,
    },
  ],
};
