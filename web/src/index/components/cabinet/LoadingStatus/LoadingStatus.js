import "./LoadingStatus.less";

import React from "react";
import PropTypes from "prop-types";
import * as UI from "../../../../ui";
import * as utils from "../../../../utils";
import SVG from "react-inlinesvg";
import { ReactComponent as LoaderIcon } from "src/asset/24px/loader.svg";

export default function LoadingStatus({
  status,
  icon,
  description,
  onRetry,
  inline
}) {
  let cont;
  switch (status) {
    case "loading":
      cont = (
        <div className="LoadingStatus__spinner">
          <LoaderIcon />
        </div>
      );
      break;
    case "failed_connection":
      cont = (
        <div className="LoadingStatus__failed">
          <SVG src={require("src/asset/120/failed.svg")} />
          <div className="LoadingStatus__failed__message">
            {utils.getLang("cabinet_loadingStatus_connectionError")}
          </div>
          {!!onRetry && (
            <UI.Button onClick={onRetry}>
              {utils.getLang("cabinet_loadingStatus_tryAgain")}
            </UI.Button>
          )}
        </div>
      );
      break;
    case "failed":
      cont = (
        <div className="LoadingStatus__failed">
          <SVG src={require("src/asset/120/failed.svg")} />
          <div className="LoadingStatus__failed__message">
            {utils.getLang("cabinet_loadingStatus_isSeemsText")}
          </div>
          {onRetry && (
            <UI.Button onClick={onRetry}>
              {utils.getLang("cabinet_loadingStatus_refresh")}
            </UI.Button>
          )}
        </div>
      );
      break;
    case "not_found":
      cont = (
        <div className="LoadingStatus__failed">
          <SVG src={require("src/asset/120/error.svg")} />
          <div className="LoadingStatus__failed__message">
            {utils.getLang("global_pageNotFound")}
          </div>
        </div>
      );
      break;
    default:
      cont = (
        <div className="LoadingStatus__failed">
          <SVG src={icon || require("src/asset/120/failed.svg")} />
          <div className="LoadingStatus__failed__message">
            {status || utils.getLang("cabinet_loadingStatus_unknownError")}
          </div>
          {description && (
            <div className="LoadingStatus__failed__description">
              {description}
            </div>
          )}
          {!!onRetry && (
            <UI.Button onClick={onRetry}>
              {utils.getLang("cabinet_loadingStatus_tryAgain")}
            </UI.Button>
          )}
        </div>
      );
      break;
  }

  return (
    <div className={utils.classNames("LoadingStatus", { inline })}>{cont}</div>
  );
}

LoadingStatus.propTypes = {
  status: PropTypes.oneOf(["loading", "failed", "failed_connection"])
    .isRequired,
  onRetry: PropTypes.func,
  inline: PropTypes.bool
};
