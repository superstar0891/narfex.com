import "./ContentItem.less";

import React, { memo } from "react";
import cn from "classnames";

export default memo(({ content, image, flip, large, accent, children }) => {
  return (
    <div className={cn("ContentItem", { flip, large, accent })}>
      {content || (
        <div
          className="ContentItem__image"
          style={{
            backgroundImage: `url(${image})`
          }}
        />
      )}
      <div className="ContentItem__content">{children}</div>
    </div>
  );
});
