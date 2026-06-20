import json, sys
sys.stdout.reconfigure(encoding='utf-8')
file = r'C:\Users\sheap\.claude\projects\A--AI-project-feature-for-elementor\baf3cd44-849b-4b7f-950a-38645d049031\tool-results\mcp-plugin_figma_figma-get_metadata-1781732943404.txt'
with open(file, encoding='utf-8') as f:
    data = json.load(f)
text = next(x['text'] for x in data if x['type'] == 'text')
lines = text.split('\n')

def print_children(target_id, label, max_depth=1):
    in_section = False
    depth_start = None
    print(f'\n=== {label} ({target_id}) ===')
    for line in lines:
        stripped = line.strip()
        indent = len(line) - len(line.lstrip())
        if f'id="{target_id}"' in line:
            in_section = True
            depth_start = indent
            print('FRAME: ' + stripped[:150])
            continue
        if in_section:
            if indent <= depth_start and stripped and '<' in stripped:
                break
            if indent <= depth_start + max_depth * 2:
                print(' ' * (indent - depth_start) + stripped[:140])

print_children('406:4318', 'Sensors group (all cards)', max_depth=1)
