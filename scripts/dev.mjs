import { spawn } from 'node:child_process'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const rootDir = dirname(dirname(fileURLToPath(import.meta.url)))
const isWindows = process.platform === 'win32'
const npmCommand = isWindows ? 'npm.cmd' : 'npm'

const processes = [
  {
    name: 'api',
    command: 'php',
    args: ['artisan', 'serve', '--host=127.0.0.1', '--port=8000'],
    cwd: join(rootDir, 'backend'),
  },
  {
    name: 'web',
    command: npmCommand,
    args: ['run', 'dev:frontend', '--', '--host=127.0.0.1', '--port=5173'],
    cwd: join(rootDir, 'frontend'),
  },
]

const running = new Set()
let shuttingDown = false

function stopAll(code = 0) {
  if (shuttingDown) {
    return
  }

  shuttingDown = true
  for (const child of running) {
    if (!child.killed) {
      child.kill()
    }
  }
  process.exitCode = code
}

for (const processConfig of processes) {
  const child = spawn(processConfig.command, processConfig.args, {
    cwd: processConfig.cwd,
    stdio: 'inherit',
    shell: isWindows,
  })

  running.add(child)

  child.on('exit', (code) => {
    running.delete(child)
    if (!shuttingDown && code !== 0) {
      stopAll(code ?? 1)
    }
  })
}

process.on('SIGINT', () => stopAll(0))
process.on('SIGTERM', () => stopAll(0))
