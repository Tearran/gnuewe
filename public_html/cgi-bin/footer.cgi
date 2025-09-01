#!/usr/bin/env python3
"""
Row-based editor for structured footer.json
Supports: links (array of groups), legal (object), about (object)
"""

import cgi
import cgitb
import json
import os
import tempfile
from html import escape

cgitb.enable()

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
JSON_FILE = os.environ.get("FOOTER_JSON_PATH") or os.path.join(SCRIPT_DIR, "..", "json", "footer.json")


def atomic_write(path, data):
    txt = json.dumps(data, indent=2, ensure_ascii=False) + "\n"
    d = os.path.dirname(path)
    os.makedirs(d, exist_ok=True)
    fd, tmp = tempfile.mkstemp(prefix=".tmp-", dir=d, text=True)
    with os.fdopen(fd, "w", encoding="utf-8") as fh:
        fh.write(txt)
        fh.flush()
        os.fsync(fh.fileno())
    os.replace(tmp, path)


def read_json():
    if not os.path.exists(JSON_FILE):
        return {"links": [], "legal": {"type": "legal", "html": ""}, "about": {"type": "about", "html": ""}}
    with open(JSON_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def html_head(title="Footer Editor"):
    return (
        "Content-Type: text/html; charset=utf-8\n\n"
        "<!doctype html><html lang='en'><head><meta charset='utf-8'>"
        f"<title>{escape(title)}</title>"
        "<style>"
        "body{font-family:system-ui;padding:1rem;max-width:900px;margin:auto;color:#111}"
        ".card{border:1px solid #ddd;padding:.75rem;border-radius:6px;background:#fafafa;margin-bottom:.75rem}"
        ".item{font-family:monospace;white-space:pre-wrap;background:#fff;padding:.5rem;border-radius:4px;border:1px solid #eee}"
        ".btn{padding:.3rem .6rem;border-radius:6px;border:1px solid #bbb;background:#eee;text-decoration:none;color:#000;margin-right:.25rem}"
        "form.inline{display:inline-block;margin:0}"
        "textarea{width:100%;font-family:monospace}"
        "</style></head><body>"
    )


def html_tail():
    return "</body></html>"


def render_links(data, form_params=None):
    out = []
    out.append("<div class='card'><h2>Links</h2>")
    for i, group in enumerate(data.get("links", [])):
        out.append(f"<h3>Group {i}: {escape(group.get('title',''))}</h3>")
        for j, item in enumerate(group.get("items", [])):
            label = escape(item.get("label", ""))
            href = escape(item.get("href", ""))
            out.append("<div class='item'>")
            out.append(f"{label} — {href} "
                       f"<a class='btn' href='?edit_link={i}&edit_item={j}'>Edit</a> "
                       f"<a class='btn' href='?delete_link={i}&delete_item={j}' onclick=\"return confirm('Delete?');\">Delete</a>")
            out.append("</div>")

        # Add form for new link in this group
        out.append(f"<div class='item'><strong>Add new link to {escape(group.get('title',''))}:</strong>")
        out.append("<form method='post'>")
        out.append(f"<input type='hidden' name='action' value='add_link'>")
        out.append(f"<input type='hidden' name='group' value='{i}'>")
        out.append("Label: <input type='text' name='label'> ")
        out.append("Href: <input type='text' name='href'> ")
        out.append("<button class='btn' type='submit'>Add</button>")
        out.append("</form></div>")

    out.append("</div>")
    return "\n".join(out)


def render_html_section(name, section):
    return (
        f"<div class='card'><h2>{name.capitalize()}</h2>"
        f"<form method='post'>"
        f"<input type='hidden' name='section' value='{escape(name)}'>"
        f"<label>HTML<br><textarea name='html' rows='6'>{escape(section.get('html',''))}</textarea></label><br>"
        f"<button class='btn' type='submit'>Save</button>"
        "</form></div>"
    )


def main():
    form = cgi.FieldStorage()
    data = read_json()
    msg = ""

    method = os.environ.get("REQUEST_METHOD", "GET").upper()
    if method == "POST":
        action = form.getfirst("action")
        if action == "add_link":
            group_idx = int(form.getfirst("group", "-1"))
            if 0 <= group_idx < len(data.get("links", [])):
                label = form.getfirst("label", "")
                href = form.getfirst("href", "")
                data["links"][group_idx]["items"].append({"label": label, "href": href})
                try:
                    atomic_write(JSON_FILE, data)
                    msg = f"Added link to group {group_idx}."
                except Exception as e:
                    msg = f"Error saving: {e}"
        elif action == "edit_link":
            group_idx = int(form.getfirst("group", "-1"))
            item_idx = int(form.getfirst("item", "-1"))
            if 0 <= group_idx < len(data.get("links", [])) and 0 <= item_idx < len(data["links"][group_idx]["items"]):
                data["links"][group_idx]["items"][item_idx]["label"] = form.getfirst("label", "")
                data["links"][group_idx]["items"][item_idx]["href"] = form.getfirst("href", "")
                try:
                    atomic_write(JSON_FILE, data)
                    msg = f"Edited link {item_idx} in group {group_idx}."
                except Exception as e:
                    msg = f"Error saving: {e}"
        elif action == "delete_link":
            group_idx = int(form.getfirst("group", "-1"))
            item_idx = int(form.getfirst("item", "-1"))
            if 0 <= group_idx < len(data.get("links", [])) and 0 <= item_idx < len(data["links"][group_idx]["items"]):
                data["links"][group_idx]["items"].pop(item_idx)
                try:
                    atomic_write(JSON_FILE, data)
                    msg = f"Deleted link {item_idx} from group {group_idx}."
                except Exception as e:
                    msg = f"Error saving: {e}"
        elif form.getfirst("section") in ["legal", "about"]:
            section = form.getfirst("section")
            data[section]["html"] = form.getfirst("html", "")
            try:
                atomic_write(JSON_FILE, data)
                msg = f"Saved {section} section."
            except Exception as e:
                msg = f"Error saving: {e}"

    print(html_head("Footer Editor"))
    if msg:
        print(f"<div class='card' style='color:green'>{escape(msg)}</div>")

    print(render_links(data))
    print(render_html_section("legal", data["legal"]))
    print(render_html_section("about", data["about"]))
    print(html_tail())


if __name__ == "__main__":
    main()
