import "./Block.less";
import React from "react";

import ContentBox from "../../../ui/components/ContentBox/ContentBox";

export default props => {
  return (
    <ContentBox className="Block">
      <div className="Block__header">
        <div className="Block__title">{props.title}</div>
        {props.rightContent && (
          <div className="Block__rightContent">{props.rightContent}</div>
        )}
      </div>
      <div>{props.children}</div>
    </ContentBox>
  );
};
