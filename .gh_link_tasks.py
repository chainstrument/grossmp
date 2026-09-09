#!/usr/bin/env python3
import subprocess, json, re, sys
REPO='chainstrument/grossmp'
# ensure epic labels exist
for i in range(1,15):
    lbl=f'epic-{i}'
    subprocess.run(['gh','label','create','-R',REPO,lbl,'--color','FFA500','--description',f'Tasks for EPIC {i}'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
# list task issues
proc=subprocess.run(['gh','issue','list','-R',REPO,'--label','task','--json','number,body','--limit','500'], capture_output=True, text=True)
if proc.returncode!=0:
    print('Failed to list issues:', proc.stderr)
    sys.exit(1)
try:
    tasks=json.loads(proc.stdout)
except json.JSONDecodeError:
    print('Failed to parse JSON from gh output')
    sys.exit(1)
for it in tasks:
    num=it.get('number')
    body=it.get('body','') or ''
    m=re.search(r'Parent Epic: #([0-9]+)', body)
    if m:
        epic_num=m.group(1)
        lbl=f'epic-{epic_num}'
        print(f'Adding label {lbl} to task #{num} and epic #{epic_num}')
        subprocess.run(['gh','issue','edit',str(num),'-R',REPO,'--add-label',lbl])
        subprocess.run(['gh','issue','edit',str(epic_num),'-R',REPO,'--add-label',lbl])
    else:
        print('No parent found in task', num)
print('Done')
