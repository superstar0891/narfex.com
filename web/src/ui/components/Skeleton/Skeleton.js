import "./Skeleton.less";

import React from "react";

import { classNames as cn } from "../../utils";

export default props => {
  return <div className={cn("Skeleton", props.className)} />;
};
