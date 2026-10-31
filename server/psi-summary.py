#!/usr/bin/env python3
"""Compact summary of a PageSpeed Insights API result.
   curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://yamaha-audio.ro/&strategy=mobile&category=performance&category=seo&category=accessibility&category=best-practices" > /tmp/psi.json
   python3 server/psi-summary.py /tmp/psi.json
"""
import json, sys

d = json.load(open(sys.argv[1], encoding='utf-8'))
lr = d.get('lighthouseResult', {})
cats = lr.get('categories', {})
aud = lr.get('audits', {})
print('URL:', lr.get('finalDisplayedUrl') or lr.get('requestedUrl'), '| form factor:', lr.get('configSettings', {}).get('formFactor'))
print('SCORES:', ', '.join(f"{k}={round((v.get('score') or 0) * 100)}" for k, v in cats.items()))
for m in ('first-contentful-paint', 'largest-contentful-paint', 'total-blocking-time', 'cumulative-layout-shift', 'speed-index', 'interactive', 'server-response-time'):
    a = aud.get(m)
    if a:
        print(f"  {m:28s} {a.get('displayValue', '')}")
el = aud.get('largest-contentful-paint-element', {}).get('details', {}).get('items', [])
if el:
    try:
        node = el[0]['items'][0]['node']
        print('LCP element:', node.get('nodeLabel', '')[:80], '|', node.get('selector', '')[:100])
    except Exception:
        pass

def rows(a, n=6):
    items = a.get('details', {}).get('items', []) or []
    out = []
    for it in items[:n]:
        url = it.get('url') or it.get('source', {}).get('url') if isinstance(it.get('source'), dict) else it.get('url')
        url = url or it.get('node', {}).get('selector') or it.get('entity') or ''
        size = it.get('wastedBytes') or it.get('totalBytes') or it.get('transferSize') or 0
        ms = it.get('wastedMs') or it.get('blockingTime') or it.get('duration') or it.get('mainThreadTime') or 0
        out.append(f"      - {str(url)[:110]}  {int(size/1024)} KB  {int(ms)} ms")
    return out

print('\nOPPORTUNITIES / DIAGNOSTICS (failing, by impact):')
fails = []
for k, a in aud.items():
    if a.get('score') is None or a.get('score', 1) >= 0.9:
        continue
    if a.get('scoreDisplayMode') in ('notApplicable', 'manual', 'informative'):
        continue
    sav = a.get('numericValue') if a.get('details', {}).get('type') == 'opportunity' else 0
    fails.append((a.get('details', {}).get('overallSavingsMs') or sav or 0, k, a))
for sav, k, a in sorted(fails, key=lambda x: -x[0]):
    print(f"  [{round((a.get('score') or 0) * 100):3d}] {k}: {a.get('displayValue', '')}")
    for r in rows(a):
        print(r)

for k in ('third-party-summary', 'mainthread-work-breakdown', 'bootup-time', 'network-requests'):
    a = aud.get(k)
    if not a:
        continue
    items = a.get('details', {}).get('items', []) or []
    print(f"\n{k}: {a.get('displayValue', '')}")
    if k == 'network-requests':
        js = [i for i in items if i.get('resourceType') == 'Script']
        css = [i for i in items if i.get('resourceType') == 'Stylesheet']
        img = [i for i in items if i.get('resourceType') == 'Image']
        font = [i for i in items if i.get('resourceType') == 'Font']
        print(f"  requests: {len(items)} | scripts {len(js)} ({sum(i.get('transferSize', 0) for i in js)//1024} KB) | css {len(css)} ({sum(i.get('transferSize', 0) for i in css)//1024} KB) | images {len(img)} ({sum(i.get('transferSize', 0) for i in img)//1024} KB) | fonts {len(font)} ({sum(i.get('transferSize', 0) for i in font)//1024} KB)")
        for i in sorted(js + css, key=lambda x: -x.get('transferSize', 0))[:15]:
            print(f"      - {i.get('url', '')[:120]}  {i.get('transferSize', 0)//1024} KB")
    else:
        for r in rows(a, 10):
            print(r)
