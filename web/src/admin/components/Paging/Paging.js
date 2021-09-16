import "./Paging.less";

import React from "react";

export default props => {
  return (
    <div className="Paging">
      <div className="Paging__total_count">
        Total count: <b>{props.totalCount}</b>
      </div>
      {props.children}
    </div>
  );
};
