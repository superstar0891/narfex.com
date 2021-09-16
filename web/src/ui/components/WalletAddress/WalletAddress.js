import "./WalletAddress.less";

import React from "react";
import PropTypes from "prop-types";
import { clipTextMiddle } from "../../../utils";
import { ReactComponent as UserIcon } from "src/asset/16px/user.svg";

const WalletAddress = props => (
  <span className="WalletAddress">
    {props.isUser && <UserIcon />}
    {clipTextMiddle(props.address)}
  </span>
);

WalletAddress.propTypes = {
  address: PropTypes.node,
  isUser: PropTypes.bool
};

export default WalletAddress;
