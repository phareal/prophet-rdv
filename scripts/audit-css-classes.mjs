#!/usr/bin/env node
// Relève les noms de classes déclarés dans les <style scoped> des composants Vue
// et signale ceux utilisés par plus d'un composant.
import { readFileSync, readdirSync } from 'node:fs'
import { join } from 'node:path'

const SOURCES = ['components', 'pages']
const files = SOURCES.flatMap((dir) =>
  readdirSync(dir, { recursive: true })
    .filter((f) => f.endsWith('.vue'))
    .map((f) => join(dir, f))
)

const byClass = new Map()

for (const file of files) {
  const src = readFileSync(file, 'utf8')
  const style = src.match(/<style scoped>([\s\S]*?)<\/style>/)
  if (!style) continue
  const classes = new Set(
    [...style[1].matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)].map((m) => m[1])
  )
  for (const cls of classes) {
    if (!byClass.has(cls)) byClass.set(cls, [])
    byClass.get(cls).push(file)
  }
}

const collisions = [...byClass.entries()]
  .filter(([, files]) => files.length > 1)
  .sort((a, b) => b[1].length - a[1].length)

console.log(`${files.length} composants analysés, ${byClass.size} classes distinctes.`)
console.log(`${collisions.length} classes utilisées par plusieurs composants :\n`)
for (const [cls, owners] of collisions) {
  console.log(`.${cls} (${owners.length})`)
  for (const owner of owners) console.log(`    ${owner}`)
}
