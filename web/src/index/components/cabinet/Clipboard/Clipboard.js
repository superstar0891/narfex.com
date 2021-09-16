import React from "react";
import * as UI from "src/ui/index";
import { copyText } from "src/actions/index";

export default props => (
  <UI.Clipboard
    title={props.title}
    skipIcon={props.skipIcon}
    className={props.className}
    onClick={() => copyText(props.title || props.text)}
    text={props.text}
    displayText={props.displayText}
  />
);
