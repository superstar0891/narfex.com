import React from "react";
import COMPANY from "../../../../index/constants/company";
import { classNames as cn } from "../../../../utils";

export default ({ className }) => (
  <div className={cn("Copyright", className)}>
    © {new Date().getYear() + 1900} {COMPANY.name}. All Rights Reserved.
  </div>
);
