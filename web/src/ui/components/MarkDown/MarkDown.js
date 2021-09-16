import React from "react";
import * as utils from "../../utils";

export default props => {
  return (
    <div
      className={props.className}
      dangerouslySetInnerHTML={{ __html: utils.parseMd(props.content) }}
    />
  );
};
