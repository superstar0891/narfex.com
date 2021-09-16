import React from "react";
import "./Promo.less";
import { Button } from "../../../../../ui";
import AppButtons from "../../../../../components/AppButtons/AppButtons";
import * as actions from "../../../../../actions/landing/buttons";
import * as pages from "../../../../../index/constants/pages";
import { useRouter, Link } from "react-router5";

export default props => {
  const router = useRouter();

  const handleClickBuyToken = e => {
    e.stopPropagation();
    e.preventDefault();
    router.navigate(pages.TOKEN);
  };
  return (
    <div className="LandingWrapper__block Promo">
      <div className="LandingWrapper__content Promo__content">
        <div className="Promo__content__text">
          <h1>{props.title}</h1>
          <p>{props.description}</p>
          <Button
            onClick={() => (props.onClick ? props.onClick() : actions.singUp())}
            className="extra_large"
          >
            {props.actionButtonText}
          </Button>
          {/*<AppButtons className="Promo__appButtons" />*/}
          {props.label && (
            <div className="Promo__buyNrfx" onClick={handleClickBuyToken}>
              <div className="Promo__buyNrfx__button">{props.label}</div>
              <div className="Promo__buyNrfx__label">
                {props.labelDescription}{" "}
                <Link routeName={pages.TOKEN}>{props.labelLink} ›</Link>
              </div>
            </div>
          )}
        </div>
        <div
          className="Promo__image"
          style={{
            backgroundImage: `url(${props.image})`
          }}
        />
      </div>
    </div>
  );
};
