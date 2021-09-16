import "./Socials.less";

import React from "react";
import COMPANY from "../../../../index/constants/company";
import SVG from "react-inlinesvg";

export default () => {
  return (
    <div className="Footer__social">
      <a
        href={"//" + COMPANY.social.facebook}
        target="__blank"
        className="Footer__social__link facebook"
      >
        <SVG src={require("src/asset/social/facebook.svg")} />
      </a>
      <a
        href={"//" + COMPANY.social.twitter}
        target="__blank"
        className="Footer__social__link twitter"
      >
        <SVG src={require("src/asset/social/twitter.svg")} />
      </a>
      <a
        href={"//" + COMPANY.social.instagram}
        target="__blank"
        className="Footer__social__link instagram"
      >
        <SVG src={require("src/asset/social/instagram.svg")} />
      </a>
      <a
        href={"//" + COMPANY.social.medium}
        target="__blank"
        className="Footer__social__link medium"
      >
        <SVG src={require("src/asset/social/medium.svg")} />
      </a>
      {/*<a*/}
      {/*  href={"//" + COMPANY.social.linkedin}*/}
      {/*  target="__blank"*/}
      {/*  className="Footer__social__link linkedin"*/}
      {/*>*/}
      {/*  <SVG src={require("src/asset/social/linkedin.svg")} />*/}
      {/*</a>*/}
    </div>
  );
};
