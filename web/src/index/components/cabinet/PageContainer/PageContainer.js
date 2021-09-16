import "./PageContainer.less";

import React from "react";
import PropTypes from "prop-types";
import cn from "classnames";
import * as UI from "../../../../ui";
import { useAdaptive } from "src/hooks";

function PageContainer({
  children,
  className,
  sideBar,
  invert,
  sidebarOptions
}) {
  const adaptive = useAdaptive();

  return (
    <div className={cn("PageContainer", className, { invert })}>
      <div className="PageContainer__sideBar">{sideBar}</div>
      <div className="PageContainer__content">{children}</div>
      {adaptive && sidebarOptions && sidebarOptions.length && (
        <UI.FloatingButton
          wrapper
          icon={require("../../../../asset/24px/options.svg")}
        >
          {sidebarOptions}
        </UI.FloatingButton>
      )}
    </div>
  );
}

PageContainer.propTypes = {
  sideBar: PropTypes.node,
  sidebarOptions: PropTypes.array
};

export default React.memo(PageContainer);
