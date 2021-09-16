import React, { memo } from "react";
import { dateFormat, ucfirst as uf } from "src/utils";
import { useSelector } from "react-redux";
import { currentLangSelector } from "../../selectors";

export default memo(({ time, format, ucfirst }) => {
  useSelector(currentLangSelector);
  const t = dateFormat(time, format);
  return <span>{ucfirst ? uf(t) : t}</span>;
});
