import React from "react";
import "./Social.less";
import SVG from "react-inlinesvg";
import Lang from "../../../../../components/Lang/Lang";
import COMPANY from "../../../../../index/constants/company";
import { classNames as cn } from "../../../../../utils";

const SnLink = ({ icon, name, type }) => (
  <a
    className={cn("Contacts__Social__item", type)}
    href={`https://${COMPANY.social[type]}`}
    target="_blank"
    rel="noopener noreferrer"
  >
    <div className="Contacts__Social__item__icon">
      <SVG src={icon} />
    </div>
    <div className="Contacts__Social__item__text">
      <h5>{name}</h5>
      <small>{COMPANY.social[type]}</small>
    </div>
  </a>
);

export default ({ accent }) => {
  return (
    <div className={cn("Contacts__Social LandingWrapper__block", { accent })}>
      <div className="LandingWrapper__content Contacts__Social__content">
        <h2>
          <Lang name="site__contactSocialNetworksTitle" />
        </h2>
        <div className="Contacts__Social__list">
          <SnLink
            type={"facebook"}
            icon={require("src/asset/social/facebook.svg")}
            name={<Lang name="global_social_facebook" />}
          />
          <SnLink
            type={"instagram"}
            icon={require("src/asset/social/instagram.svg")}
            name={<Lang name="global_social_instagram" />}
          />
          <SnLink
            type={"twitter"}
            icon={require("src/asset/social/twitter.svg")}
            name={<Lang name="global_social_twitter" />}
          />
          <SnLink
            type={"medium"}
            icon={require("src/asset/social/medium.svg")}
            name={<Lang name="global_social_medium" />}
          />
          {/*<SnLink*/}
          {/*  icon={require('src/asset/social/linkedin.svg')}*/}
          {/*  link={COMPANY.social.linkedin}*/}
          {/*  name={<Lang name="global_social_linkedin" />}*/}
          {/*/>*/}
        </div>
      </div>
    </div>
  );
};
