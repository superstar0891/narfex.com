import React, { useCallback, useState, useRef } from "react";
import "./Timeline.less";

import SVG from "react-inlinesvg";
import { classNames as cn } from "src/utils";
import Lang from "src/components/Lang/Lang";
import Time from "src/components/Time/Time";

export default ({ title, timeLine, className }) => {
  const [position, setPosition] = useState(0);
  const scrollRef = useRef(null);

  const handleScroll = useCallback(e => {
    setPosition(
      e.target.scrollLeft / (e.target.scrollWidth - e.target.clientWidth)
    );
  }, []);

  const handleClickRight = useCallback(
    e => {
      scrollRef.current.scrollLeft =
        scrollRef.current.scrollLeft + scrollRef.current.clientWidth;
    },
    [scrollRef]
  );

  const handleClickLeft = useCallback(
    e => {
      scrollRef.current.scrollLeft =
        scrollRef.current.scrollLeft - scrollRef.current.clientWidth;
    },
    [scrollRef]
  );

  return (
    <div className="Timeline">
      <div className="Timeline__timelineWrapper">
        <div
          onClick={handleClickLeft}
          className={cn("Timeline__timelineWrapper__button left", {
            active: position > 0
          })}
        >
          <div className="Timeline__timelineWrapper__button__icon">
            <SVG src={require("src/asset/24px/angle-left-small.svg")} />
          </div>
        </div>
        <div
          onClick={handleClickRight}
          className={cn("Timeline__timelineWrapper__button right", {
            active: position < 1
          })}
        >
          <div className="Timeline__timelineWrapper__button__icon">
            <SVG src={require("src/asset/24px/angle-right-small.svg")} />
          </div>
        </div>
        <div
          onScroll={handleScroll}
          ref={scrollRef}
          className="Timeline__scrollWrapper"
        >
          <ul className="Timeline__timeline">
            {timeLine?.map(event => {
              const future = event.time * 1000 > Date.now();
              return (
                <li className={cn("item", { future })}>
                  <h4>
                    <Lang name={event.titleLang} />
                  </h4>
                  <p>
                    <Lang name={event.textLang} />
                  </p>
                  <small>
                    <Time
                      ucfirst
                      time={event.time}
                      // format={future ? "MMMM YYYY" : "D MMMM YYYY"}
                      format={"D MMMM YYYY"}
                    />
                  </small>
                </li>
              );
            })}
          </ul>
        </div>
      </div>
    </div>
  );
};
