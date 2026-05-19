import re

def main():
    # 1. Sync admin_dashboard.html
    with open('admin/index.php', 'r', encoding='utf-8') as f:
        content = f.read()

    # Remove PHP block at the top
    content_clean = re.sub(r'^<\?php.*?\?>\s*', '', content, flags=re.DOTALL)

    # Replace PHP echo variables in JS
    content_clean = re.sub(r'<\?php\s+echo\s+\$userName;\s+\?>', 'Admin', content_clean)
    content_clean = re.sub(r'<\?php\s+echo\s+\$userRole;\s+\?>', 'Admin', content_clean)
    content_clean = re.sub(r'<\?php\s+echo\s+\$userEmail;\s+\?>', 'alcaeusposa@gmail.com', content_clean)

    # Write to admin_dashboard.html
    with open('admin_dashboard.html', 'w', encoding='utf-8') as f:
        f.write(content_clean)
    print("admin_dashboard.html synchronized successfully!")

    # 2. Sync client_dashboard.html
    with open('client/index.php', 'r', encoding='utf-8') as f:
        client_content = f.read()

    # Remove PHP block at the top
    client_clean = re.sub(r'^<\?php.*?\?>\s*', '', client_content, flags=re.DOTALL)

    # Replace PHP echo variables in JS
    client_clean = re.sub(r'<\?php\s+echo\s+\$userName;\s+\?>', 'Client User', client_clean)
    client_clean = re.sub(r'<\?php\s+echo\s+\$userRole;\s+\?>', 'client', client_clean)
    client_clean = re.sub(r'<\?php\s+echo\s+\$userEmail;\s+\?>', 'borgir@gmail.com', client_clean)
    client_clean = re.sub(r'<\?php\s+echo\s+\$user_id;\s+\?>', '24', client_clean)

    # Write to client_dashboard.html
    with open('client_dashboard.html', 'w', encoding='utf-8') as f:
        f.write(client_clean)
    print("client_dashboard.html synchronized successfully!")

if __name__ == '__main__':
    main()
