import "./Lang.less";

import React, { memo, useCallback } from "react";
import PropTypes from "prop-types";
import { useSelector } from "react-redux";
import {
  currentLangSelector,
  langSelector,
  settingsTranslatorSelector
} from "../../selectors";
import { joinComponents } from "../../utils";
import { openStateModal } from "../../actions";

const Lang = memo(({ name, params }) => {
  const handleClick = useCallback(
    e => {
      e.preventDefault();
      openStateModal("translator", { langKey: name });
    },
    [name]
  );

  const translatorMode = useSelector(settingsTranslatorSelector);
  const currentLang = useSelector(currentLangSelector);
  const lang = useSelector(langSelector(currentLang, name));
  let displayLang = lang || name || "";

  if (params !== undefined || displayLang.includes("\\n")) {
    displayLang = displayLang
      .split(/({.*?})|(\\n)/g)
      .filter(Boolean)
      .map(str => {
        if (str === "\\n") return <br />;
        return str.includes("{") ? params[str.slice(1, -1)] || "" : str;
      })
      .reduce(joinComponents(""), null);
  } else {
    displayLang = displayLang.replace(/{.*?}/g, "");
  }

  if (translatorMode) {
    return (
      <span onContextMenu={handleClick} className="Lang">
        {displayLang}
      </span>
    );
  } else {
    return displayLang;
  }
});

Lang.propTypes = {
  name: PropTypes.string,
  params: PropTypes.object
};

export default Lang;
