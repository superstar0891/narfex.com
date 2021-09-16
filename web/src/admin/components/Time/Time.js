import React, { memo } from "react";
import { dateFormat, ucfirst as uf } from "src/utils";

export default memo(({ time, format, ucfirst }) => {
  const t = dateFormat(time, format);
  return <span>{ucfirst ? uf(t) : t}</span>;
});
