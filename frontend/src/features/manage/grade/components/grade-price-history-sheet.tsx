import BaseSheet from "@/components/shared/base-sheet";
import {
  Col,
  Card,
  Spin,
  Empty,
  Row,
  Timeline,
  Typography,
  DatePicker,
} from "antd";
import React, { useState } from "react";
import { IGrade } from "../type";
import { useTranslations } from "next-intl";
import dayjs from "dayjs";
import { useQuery } from "@tanstack/react-query";
import { getGradePriceCurrent, getGradePriceHistory } from "../actions";
import {
  DollarOutlined,
  HistoryOutlined,
  LineChartOutlined,
} from "@ant-design/icons";
import dynamic from "next/dynamic";
import { formatVnCurrency } from "@/lib/utils";

const DualAxes = dynamic(
  () => import("@ant-design/plots").then((mod) => mod.DualAxes),
  {
    ssr: false,
  },
);

const { Text } = Typography;

interface GradePriceHistorySheetProps {
  open: boolean;
  onClose: () => void;
  record: IGrade | null;
}

const GradePriceHistorySheet = ({
  open,
  onClose,
  record,
}: GradePriceHistorySheetProps) => {
  const t = useTranslations("Manage.Grade");
  const tc = useTranslations("Common");

  const [dateRange, setDateRange] = useState<
    [dayjs.Dayjs | null, dayjs.Dayjs | null] | null
  >(null);

  React.useEffect(() => {
    if (open) {
      setDateRange(null);
    }
  }, [open, record?.grade_code]);

  const effective_from = dateRange?.[0]
    ? dateRange[0].format("YYYY-MM-DD")
    : undefined;
  const effective_to = dateRange?.[1]
    ? dateRange[1].format("YYYY-MM-DD")
    : undefined;

  // Fetch current price
  const { data: currentPriceRes, isLoading: isLoadingCurrent } = useQuery({
    queryKey: ["grade-price-current", record?.grade_code],
    queryFn: () => getGradePriceCurrent(record!.grade_code),
    enabled: open && !!record?.grade_code,
  });

  // Fetch history list
  const { data: priceHistoryRes, isLoading: isLoadingHistory } = useQuery({
    queryKey: [
      "grade-price-history",
      record?.grade_code,
      effective_from,
      effective_to,
    ],
    queryFn: () =>
      getGradePriceHistory({
        grade_code: record!.grade_code,
        page: 1,
        limit: 100,
        effective_from,
        effective_to,
      }),
    enabled: open && !!record?.grade_code,
  });

  const currentPrice = currentPriceRes?.data?.price;
  const historyRecords = priceHistoryRes?.data?.records || [];

  // Sort history records chronologically (oldest first) for Recharts line chart representation
  const chartData = [...historyRecords]
    .map((item) => ({
      date: dayjs(item.effective_from).format("DD/MM/YYYY"),
      domestic: Number(item.domestic_price),
      international: Number(item.international_price),
      rawDate: item.effective_from,
    }))
    .sort((a, b) => dayjs(a.rawDate).unix() - dayjs(b.rawDate).unix());

  // Render current price summary card
  const renderCurrentPriceCard = () => {
    if (isLoadingCurrent) {
      return (
        <Card
          style={{ marginBottom: 16 }}
          className="border border-slate-100 shadow-sm">
          <div className="flex justify-center items-center py-4">
            <Spin size="small" />
          </div>
        </Card>
      );
    }

    const domestic = currentPrice
      ? Number(currentPrice.domestic_price)
      : record?.current_domestic_price;
    const international = currentPrice
      ? Number(currentPrice.international_price)
      : record?.current_international_price;
    const dateFrom =
      currentPrice?.effective_from || record?.current_price_effective_from;
    const dateTo =
      currentPrice?.effective_to || record?.current_price_effective_to;

    return (
      <Card
        style={{ marginBottom: 16, borderRadius: 8 }}
        className="bg-slate-50 border border-slate-200 shadow-sm">
        <div className="flex items-center gap-2 mb-3">
          <DollarOutlined className="text-blue-600 text-lg" />
          <Text className="font-bold text-slate-800 text-sm">
            {t("active_price")}
          </Text>
        </div>
        <Row gutter={16}>
          <Col span={12}>
            <div className="flex flex-col">
              <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">
                {t("current_domestic_price")}
              </span>
              <span className="text-lg font-bold text-slate-900 mt-1">
                {domestic ? `${domestic.toLocaleString()} VNĐ` : ""}
              </span>
            </div>
          </Col>
          <Col span={12}>
            <div className="flex flex-col border-l border-slate-200 pl-4">
              <span className="text-xs text-slate-500 font-medium uppercase tracking-wider">
                {t("current_international_price")}
              </span>
              <span className="text-lg font-bold text-slate-900 mt-1">
                {international ? `$${international.toLocaleString()}` : ""}
              </span>
            </div>
          </Col>
        </Row>
        {dateFrom && (
          <div className="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-500 flex justify-between">
            <span>
              {t("current_price_effective_from")}:{" "}
              <span className="font-semibold text-slate-700">
                {dayjs(dateFrom).format("DD/MM/YYYY")}
              </span>
            </span>
            {dateTo && (
              <span>
                {t("current_price_effective_to")}:{" "}
                <span className="font-semibold text-slate-700">
                  {dayjs(dateTo).format("DD/MM/YYYY")}
                </span>
              </span>
            )}
          </div>
        )}
      </Card>
    );
  };

  return (
    <BaseSheet
      open={open}
      onClose={onClose}
      title={`${t("price_history_title")} - ${record?.name || ""}`}
      width={"70%"}>
      {renderCurrentPriceCard()}

      <DatePicker.RangePicker
        value={dateRange}
        onChange={(val) => setDateRange(val)}
        format="DD/MM/YYYY"
        placeholder={[t("start_date"), t("end_date")]}
      />

      {/* Price History Line Chart using Ant Design Plots DualAxes */}
      <Card
        style={{ marginBottom: 16, borderRadius: 8 }}
        className="border border-slate-200 shadow-sm mt-1!">
        <div className="flex items-center gap-2 mb-4">
          <LineChartOutlined className="text-blue-600 text-lg" />
          <Text className="font-bold text-slate-800 text-sm">
            {t("price_history_chart")}
          </Text>
        </div>
        {isLoadingHistory ? (
          <div className="flex justify-center items-center h-64">
            <Spin />
          </div>
        ) : chartData.length === 0 ? (
          <div className="flex justify-center items-center h-64 bg-slate-50 rounded-lg">
            <Empty
              image={Empty.PRESENTED_IMAGE_SIMPLE}
              description={t("no_history")}
            />
          </div>
        ) : (
          <div className="w-full h-64">
            <DualAxes
              height={256}
              xField="date"
              legend={{
                color: {
                  position: "bottom",
                  layout: "horizontal",
                },
              }}
              tooltip={{
                shared: true,
                showMarkers: true,
              }}
              {...{
                children: [
                  {
                    data: chartData,
                    type: "line",
                    yField: "domestic",
                    colorField: t("current_domestic_price"),
                    style: {
                      stroke: "#2563eb",
                      lineWidth: 2,
                    },
                    axis: {
                      y: {
                        title: false,
                        labelFormatter: (val: any) =>
                          `${(Number(val) / 1000).toFixed(0)}k`,
                      },
                    },
                    scale: {
                      y: {
                        independent: true,
                      },
                    },
                  },
                  {
                    data: chartData,
                    type: "line",
                    yField: "international",
                    colorField: t("current_international_price"),
                    style: {
                      stroke: "#10b981",
                      lineWidth: 2,
                    },
                    axis: {
                      y: {
                        title: false,
                        position: "right",
                        labelFormatter: (val: any) =>
                          `$${Number(val).toLocaleString()}`,
                      },
                    },
                    scale: {
                      y: {
                        independent: true,
                      },
                    },
                  },
                ],
              }}
            />
          </div>
        )}
      </Card>

      {/* Timeline pricing list */}
      <Card
        style={{ borderRadius: 8 }}
        className="border border-slate-200 shadow-sm">
        <div className="flex items-center gap-2 mb-4">
          <HistoryOutlined className="text-blue-600 text-lg" />
          <Text className="font-bold text-slate-800 text-sm">
            {t("price_history_title")}
          </Text>
        </div>
        {isLoadingHistory ? (
          <div className="flex justify-center items-center py-12">
            <Spin />
          </div>
        ) : historyRecords.length === 0 ? (
          <Empty
            image={Empty.PRESENTED_IMAGE_SIMPLE}
            description={t("no_history")}
            className="my-8"
          />
        ) : (
          <div
            style={{
              maxHeight: "calc(100vh - 450px)",
              overflowY: "auto",
              padding: "8px 4px",
            }}>
            <Timeline>
              {historyRecords.map((item) => {
                const domesticNum = Number(item.domestic_price);
                const internationalNum = Number(item.international_price);
                return (
                  <Timeline.Item key={item.grade_price_id} color="blue">
                    <div className="flex flex-col bg-white border border-slate-100 rounded-lg p-3 shadow-xs hover:shadow-sm transition-all duration-200">
                      <div className="flex justify-between items-center mb-1">
                        <span className="text-xs text-slate-400 font-medium">
                          {dayjs(item.effective_from).format("DD/MM/YYYY")}
                        </span>
                        <span className="font-bold text-slate-800 text-sm">
                          {domesticNum
                            ? `${domesticNum.toLocaleString()} VNĐ`
                            : ""}
                        </span>
                        <span className="font-bold text-slate-800 text-sm">
                          {internationalNum
                            ? `$${internationalNum.toLocaleString()}`
                            : ""}
                        </span>
                      </div>
                      <span className="text-xs text-slate-400">
                        {t("current_price_effective_from")}:{" "}
                        {dayjs(item.effective_from).format("DD/MM/YYYY")}
                        {item.effective_to &&
                          ` - ${t("current_price_effective_to")}: ${dayjs(item.effective_to).format("DD/MM/YYYY")}`}
                      </span>
                      {item.note && (
                        <div className="mt-2 pt-2 border-t border-dashed border-slate-100 text-xs text-slate-500 italic">
                          {tc("notes")}: {item.note}
                        </div>
                      )}
                    </div>
                  </Timeline.Item>
                );
              })}
            </Timeline>
          </div>
        )}
      </Card>
    </BaseSheet>
  );
};

export default GradePriceHistorySheet;
