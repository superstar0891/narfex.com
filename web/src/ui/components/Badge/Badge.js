import "./Badge.less";

import React from "react";

function Badge({ count, children, onClick }) {
  const handleClick = e => {
    onClick && onClick(e);
  };

  return (
    <div className="Badge" onClick={handleClick}>
      {children}
      <span className="Badge__count">{count}</span>
    </div>
  );
}

export default Badge;
