import { CheckOutlined } from "@ant-design/icons";
import { Collapse, Empty, Typography } from "antd";
import { ReactNode, useMemo } from "react";
import {
  RSS_QUALITY_OPTIONS,
  RssQuality,
  getRssQualityLabel,
} from "./rss-process-state";

const { Text } = Typography;

export type RssPickerValue = string | number;

export interface RssPickerOption<T extends RssPickerValue = string> {
  value: T;
  label: ReactNode;
  description?: ReactNode;
  meta?: ReactNode;
  disabled?: boolean;
}

interface RssTilePickerProps<T extends RssPickerValue = string> {
  ariaLabel: string;
  options: RssPickerOption<T>[];
  selectedValue?: T | null;
  selectedValues?: T[];
  multiple?: boolean;
  emptyText?: string;
  className?: string;
  onChange: (value: T | T[]) => void;
}

export const RssTilePicker = <T extends RssPickerValue = string>({
  ariaLabel,
  options,
  selectedValue,
  selectedValues,
  multiple = false,
  emptyText = "Chưa có dữ liệu",
  className,
  onChange,
}: RssTilePickerProps<T>) => {
  const activeValues = useMemo(
    () =>
      new Set<T>(
        selectedValues ??
          (selectedValue !== undefined && selectedValue !== null
            ? [selectedValue]
            : []),
      ),
    [selectedValue, selectedValues],
  );

  const handleSelect = (option: RssPickerOption<T>) => {
    if (option.disabled) return;

    if (!multiple) {
      onChange(option.value);
      return;
    }

    const nextValues = activeValues.has(option.value)
      ? Array.from(activeValues).filter((value) => value !== option.value)
      : [...Array.from(activeValues), option.value];

    onChange(nextValues);
  };

  if (options.length === 0) {
    return (
      <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description={emptyText} />
    );
  }

  return (
    <div
      className={["rss-option-picker", className].filter(Boolean).join(" ")}
      role="group"
      aria-label={ariaLabel}>
      {options.map((option) => {
        const isSelected = activeValues.has(option.value);

        return (
          <button
            key={String(option.value)}
            type="button"
            className={[
              "rss-option-tile",
              isSelected ? "rss-option-tile-selected" : "",
              option.disabled ? "rss-option-tile-disabled" : "",
            ]
              .filter(Boolean)
              .join(" ")}
            aria-pressed={isSelected}
            disabled={option.disabled}
            onClick={() => handleSelect(option)}>
            <span className="rss-option-tile-title">{option.label}</span>
            {option.description ? (
              <span className="rss-option-tile-description">
                {option.description}
              </span>
            ) : null}
            {option.meta ? (
              <span className="rss-option-tile-meta">{option.meta}</span>
            ) : null}
            {isSelected ? (
              <CheckOutlined className="rss-option-tile-icon" />
            ) : null}
          </button>
        );
      })}
    </div>
  );
};

export const RssQualityPicker = ({
  value,
  onChange,
}: {
  value?: RssQuality;
  onChange: (quality: RssQuality) => void;
}) => (
  <RssTilePicker
    ariaLabel="Chọn chất lượng"
    className="rss-quality-picker"
    selectedValue={value ?? "NA"}
    options={RSS_QUALITY_OPTIONS.map((option) => ({
      value: option.value,
      label: option.label,
      description: option.desc,
    }))}
    onChange={(nextValue) => onChange(nextValue as RssQuality)}
  />
);

export const RssQualityFlowPicker = ({
  value,
  onChange,
}: {
  value: RssQuality | "all";
  onChange: (quality: RssQuality | "all") => void;
}) => (
  <RssTilePicker<RssQuality | "all">
    ariaLabel="Chọn chất lượng"
    className="rss-quality-picker"
    selectedValue={value}
    options={[
      {
        value: "all",
        label: "Tất cả",
        description: "Hiện tất cả",
      },
      ...RSS_QUALITY_OPTIONS.map((option) => ({
        value: option.value,
        label: option.label,
        description: option.desc,
      })),
    ]}
    onChange={(nextValue) => onChange(nextValue as RssQuality | "all")}
  />
);

export const RssSelectedSummary = ({
  label,
  values,
}: {
  label: string;
  values: Array<string | number>;
}) => (
  <Text type="secondary">
    {label}: {values.length > 0 ? values.join(", ") : "Chưa chọn"}
  </Text>
);

export const RssTechnicalDetails = ({
  summary = "Chi tiết kỹ thuật",
  children,
}: {
  summary?: ReactNode;
  children: ReactNode;
}) => (
  <Collapse
    className="rss-technical-details"
    size="small"
    items={[
      {
        key: "1",
        label: summary,
        children: <div className="rss-technical-details-body">{children}</div>,
      },
    ]}
  />
);

export const formatQualityFlowLabel = (quality: RssQuality | "all") =>
  quality === "all" ? "Tất cả chất lượng" : getRssQualityLabel(quality);
