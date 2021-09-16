import "./Logo.less";

import React from "react";
import PropTypes from "prop-types";
import { classNames as cn } from "../../utils/";
import { ReactComponent as DefaultLogo } from "src/asset/logo/default.svg";
import { ReactComponent as BitcoinovnetLogo } from "src/asset/logo/bintoinov-net.svg";

const Logo = props => {
  let Logo;
  switch (props.type) {
    case "bitcoinovnet":
      Logo = BitcoinovnetLogo;
      break;
    default:
      Logo = DefaultLogo;
      break;
  }

  return (
    <div
      onClick={props.onClick}
      className={cn("Logo", props.size, props.type, props.className, {
        currentColor: props.currentColor
      })}
    >
      <Logo />
    </div>
  );
};

Logo.defaultProps = {
  type: "default",
  size: "middle"
};

Logo.propTypes = {
  size: PropTypes.oneOf(["middle", "large"])
};

export default React.memo(Logo);
