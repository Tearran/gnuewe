---
title: Example
tags:
  - docs
  - viewer
  - markdown
  - example
---

# Markdown Syntax Demo

Welcome to the **Markdown Syntax Demo**.  
This document covers all major Markdown features to test a parser.

---

## 1. Headings

# H1 Heading
## H2 Heading
### H3 Heading
#### H4 Heading
##### H5 Heading
###### H6 Heading

Setext-style:
Title Level 1
=============
Title Level 2
-------------

---

## 2. Paragraphs & Line Breaks

This is a paragraph.

This is another paragraph,  
with a manual line break after "paragraph,".

---

## 3. Emphasis

*Italic* or _Italic_  
**Bold** or __Bold__  
***Bold and Italic***  
~~Strikethrough~~

---

## 4. Links & Images

Link: [GitHub](https://github.com)  
Image: ![Alt text](https://via.placeholder.com/48 "Placeholder")

---

## 5. Inline Code & Code Blocks

Inline code: `echo "Hello, world!"`

Inline code with backticks: ``Use `code` inside double backticks.``

Fenced code block (with language):
```php
// PHP example
echo "Hello, world!";
```

Indented code block:
    function foo() {
        return "bar";
    }

---

## 6. Lists

Unordered list:
- Item 1
- Item 2
    - Nested Item 2.1
    - Nested Item 2.2

* Item 3 (asterisk)

+ Item 4 (plus)

Ordered list:
1. First
2. Second
    1. Sub-item a
    2. Sub-item b

---

## 7. Blockquotes

> This is a blockquote.
>
> - It can span multiple lines.
> - And include lists
> 
> > Nested blockquote

---

## 8. Horizontal Rule

---

## 9. Tables

| Name    | Age | City      |
|---------|-----|-----------|
| Alice   | 23  | Seattle   |
| Bob     | 34  | Portland  |
| **Sum** | 57  | —         |

---

## 10. Escaping

\*This text is not italic\*  
\# Not a heading  
\\ Backslash

---

## 11. HTML passthrough

<div style="color:red">This is raw HTML.</div>

---

## 12. Mix: All Together

> **Note:** Use `code` and [links](https://example.com) in blockquotes and lists.
> 
> Example:
> 
> - List item with `inline code`
> - ![img](https://via.placeholder.com/32)
> - [Link](https://example.com)
> 
> ```
> code block in blockquote
> ```

---

End of demo.