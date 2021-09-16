import React from "react";
import { openModal } from "src/actions";
import { nl2br } from "src/utils/index";
import "./TranslatorMode.less";

const TranslatorMode = ({ langKey, langContent }) => {
  return (
    <span
      className="Translation"
      onContextMenu={e => {
        e.preventDefault();
        openModal("translator", { langKey });
      }}
    >
      {typeof langContent === "string" ? nl2br(langContent) : langContent}
    </span>
  );
};

export default TranslatorMode;
