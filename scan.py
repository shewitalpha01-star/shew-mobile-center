with open('index.html', 'r', encoding='utf-8') as f:
    content = f.read()
script_start = content.rfind('<script>')
script = content[script_start:]
found = []
for i, c in enumerate(script):
    if ord(c) > 127:
        found.append(f'pos={i} char={repr(c)} ord={ord(c)} ctx={repr(script[max(0,i-40):i+40])}')
if found:
    for x in found:
        print(x)
else:
    print('NO NON-ASCII FOUND')
