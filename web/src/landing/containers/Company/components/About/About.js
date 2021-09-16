import React, { useEffect, useState } from "react";
import { useSelector } from "react-redux";
import { getStaticPageContent } from "src/actions";
import { Editor } from "../../../../../ui";
import "./About.less";
import { currentLangSelector } from "../../../../../selectors";
import LoadingStatus from "../../../../../index/components/cabinet/LoadingStatus/LoadingStatus";

export default () => {
  const [content, setContent] = useState(null);
  const lang = useSelector(currentLangSelector);

  useEffect(() => {
    getStaticPageContent("company").then(r => {
      setContent(r);
    });
  }, [lang]);

  return (
    <div className="Company__About LandingWrapper__block">
      <div className="LandingWrapper__content Company__About__content">
        {content ? (
          <div>
            <h1>{content.title}</h1>
            <Editor autoUpdate content={content.content} readOnly />
          </div>
        ) : (
          <LoadingStatus inline status="loading" />
        )}
      </div>
    </div>
  );
};
