import "./EditorTooltip.less";
import React from "react";

import { classNames as cn } from "../../utils";

export default props => {
  return (
    <div
      style={props.style}
      className={cn("EditorTooltip", props.className, {
        visible: props.visible
      })}
    >
      <div
        onMouseDown={() => props.onToggleBlockType("header-one")}
        className="EditorTooltip__item h1"
      >
        H1
      </div>
      <div
        onMouseDown={() => props.onToggleBlockType("header-two")}
        className="EditorTooltip__item h2"
      >
        H2
      </div>
      <div
        onMouseDown={() => props.onToggleBlockType("blockquote")}
        className="EditorTooltip__item blockquote"
      >
        “quote”
      </div>
      <div onMouseDown={props.onSetLint} className="EditorTooltip__item">
        link
      </div>
      <div
        onMouseDown={() => props.onToggleInlineStyle("BOLD")}
        className="EditorTooltip__item bold"
      >
        Bold
      </div>
      <div
        onMouseDown={() => props.onToggleInlineStyle("CODE")}
        className="EditorTooltip__item code"
      >
        [code]
      </div>
      <div
        onMouseDown={() => props.onToggleBlockType("code")}
        className="EditorTooltip__item code"
      >{`<CODE />`}</div>
      <div
        onMouseDown={() => props.onToggleInlineStyle("ITALIC")}
        className="EditorTooltip__item Italic"
      >
        Italic
      </div>
      <div
        onMouseDown={() => props.onToggleInlineStyle("UNDERLINE")}
        className="EditorTooltip__item Underline"
      >
        Underline
      </div>
    </div>
  );
};
