import "./PageContainerOld.less";

import React from "react";
import PropTypes from "prop-types";
import * as UI from "../../../../ui";
import ProfileSidebar from "../../cabinet/ProfileSidebar/ProfileSidebar";
import { useAdaptive } from "src/hooks";

function PageContainerOld({ children, leftContent, sidebarOptions }) {
  const adaptive = useAdaptive();

  return (
    <div className="PageContainerOld">
      {!adaptive && <ProfileSidebar sidebarOptions={sidebarOptions} />}

      <div className="PageContainerOld__content">
        <div className="PageContainerOld__content__primary">{children}</div>

        {leftContent && (
          <div className="PageContainerOld__content__secondary">
            {leftContent}
          </div>
        )}
      </div>
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

PageContainerOld.propTypes = {
  leftContent: PropTypes.node,
  sidebarOptions: PropTypes.array
};

export default React.memo(PageContainerOld);
