```js
<EditorTooltip
  onChange={console.log}
  visible={true}
  buttons={[
    {
      title: "H1",
      block: "header-one"
    },
    {
      title: "H2",
      block: "header-two"
    },
    {
      title: "“quote”",
      block: "blockquote"
    },
    {
      title: "<code />",
      style: "code-block"
    },
    {
      title: "Bold",
      style: "BOLD"
    },
    {
      title: "Italic",
      style: "ITALIC"
    },
    {
      title: "Underline",
      style: "UNDERLINE"
    }
    // {
    //   title: "Monospace",
    //   style: "MONOSPACE"
    // }
  ]}
/>
```
