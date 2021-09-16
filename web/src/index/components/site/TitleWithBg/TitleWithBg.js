import "./TitleWithBg.less";

import React from "react";

import { classNames } from "../../../../utils";

function TitleWithBg({ title, bgTitle, bgTitleUppercase, darkBg, centered }) {
  const className = classNames({
    TitleWithBg: true,
    centered
  });

  const bgClassName = classNames({
    TitleWithBg__title__bg: true,
    bgTitleUppercase,
    darkBg
  });

  return (
    <div className={className}>
      {title}
      <div className={bgClassName}>{bgTitle}</div>
    </div>
  );
}

export default React.memo(TitleWithBg);
