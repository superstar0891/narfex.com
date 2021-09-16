import React from "react";
import "./Clipboard.less";
import Clipboard from "src/index/components/cabinet/Clipboard/Clipboard";
import { clipTextMiddle } from "src/utils/index";

export default ({ text, length, skip_icon }) => {
  const content = clipTextMiddle(text, length);
  if (!text) {
    return "-";
  }
  return (
    <Clipboard
      className="AdminClipboard"
      text={content}
      title={text}
      skipIcon={skip_icon}
    />
  );
};
