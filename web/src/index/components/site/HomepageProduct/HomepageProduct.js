import "./HomepageProduct.less";

import React from "react";
import PropTypes from "prop-types";

import TitleWithBg from "../TitleWithBg/TitleWithBg";
import * as utils from "../../../../utils";
import * as UI from "../../../../ui";

export default function HomepageProduct(props) {
  const className = utils.classNames({
    HomepageProduct: true,
    reverse: props.reverse
  });

  const iconClassName = utils.classNames({
    HomepageProduct__icon: true,
    [props.icon]: true
  });

  return (
    <div className={className}>
      <div className={iconClassName} />
      <div className="HomepageProduct__cont">
        <TitleWithBg title={props.title} bgTitle={props.bgTitle} />

        <ul className="HomepageProduct__caption">
          {React.Children.map(props.children, child => {
            return (
              <li>
                <span>{child}</span>
              </li>
            );
          })}
        </ul>

        <a href={`/${props.seeMoreLink}`} className="HomepageProduct__anchor">
          <UI.Button
            fontSize={15}
            rounded
            afterContent={<div className="HomepageProduct__button_arrow" />}
          >
            {utils.getLang("site_readMore")}
          </UI.Button>
        </a>
      </div>
    </div>
  );
}

HomepageProduct.propTypes = {
  title: PropTypes.string.isRequired,
  bgTitle: PropTypes.string.isRequired,
  icon: PropTypes.string.isRequired,
  reverse: PropTypes.bool,
  style: PropTypes.object
};
