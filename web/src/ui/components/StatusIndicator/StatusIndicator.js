import "./StatusIndicator.less";

import React from "react";
import { classNames as cn } from "../../utils";

export default ({ status }) => (
  <div
    title={status}
    className={cn("StatusIndicator", status)}
    children={status}
  />
);
