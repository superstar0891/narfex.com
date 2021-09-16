import "./LoadingMore.less";

import React from "react";
import PropTypes from "prop-types";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";

export default function LoadingMore({ status, onClick }) {
  // let cont;
  // if (status === "loading") {
  //   cont = <div className="LoadingStatus__spinner LoadingMore__loader" />;
  // } else {
  //   cont = (
  //     <UI.Button>
  //       {status === "failed"
  //         ? utils.getLang("cabinet_loadingMore_retry")
  //         : utils.getLang("cabinet_loadingMore_more")}
  //     </UI.Button>
  //   );
  // }

  return (
    <div className="LoadingMore" onClick={onClick || (() => {})}>
      <UI.Button state={status}>
        {status === "failed"
          ? utils.getLang("cabinet_loadingMore_retry")
          : utils.getLang("cabinet_loadingMore_more")}
      </UI.Button>
    </div>
  );
}

LoadingMore.propTypes = {
  status: PropTypes.oneOf(["loading", "failed", ""]),
  onClick: PropTypes.func
};
