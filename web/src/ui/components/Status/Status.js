import "./Status.less";

import React, { memo } from "react";
import { classNames as cn } from "../../utils";
import Lang from "../../../components/Lang/Lang";

import { ReactComponent as ClockIcon } from "src/asset/24px/clock.svg";

export default memo(({ status, label, indicator }) => (
  <span className={cn("Status", status)}>
    {status === "pending" && <ClockIcon />}
    {/*{indicator && ( status === "pending" ? <ClockIcon /> : <div className="Status__indicator" />)}*/}
    {label || <Lang name={"status_" + status} />}
  </span>
));
