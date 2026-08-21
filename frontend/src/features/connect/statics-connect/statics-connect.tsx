"use client";
import React, { useState, useMemo } from "react";
import {
  Card,
  Col,
  Row,
  Select,
  Space,
  Button,
  Typography,
  Spin,
  Empty,
  Flex,
  Form,
} from "antd";
import { useQuery } from "@tanstack/react-query";
import dynamic from "next/dynamic";
import {
  getConnections,
  IGetConnectionParams,
} from "../manage-connect/actions";

const Pie = dynamic(() => import("@ant-design/plots").then((mod) => mod.Pie), {
  ssr: false,
});

const { Text } = Typography;

import { useTranslations } from "next-intl";

const StaticsConnect = () => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");
  const tRegister = useTranslations("Register");

  const defaultParams: Partial<IGetConnectionParams> = {
    page: 1,
    limit: 100,
    status: "all",
    type: "all",
  };

  const [params, setParams] =
    useState<Partial<IGetConnectionParams>>(defaultParams);

  const { data, isFetching } = useQuery({
    queryKey: ["connections-statistics", params],
    queryFn: () => getConnections(params),
  });

  const records = useMemo(() => data?.data?.records || [], [data]);

  const directionStats = useMemo(() => {
    const received = records.filter(
      (r) => r.connection_direction === "received",
    ).length;
    const sent = records.filter(
      (r) => r.connection_direction === "sent",
    ).length;

    return [
      { name: tConnection("received"), value: received, color: "#3b82f6" }, // Màu xanh dương
      { name: tConnection("sent"), value: sent, color: "#4ade80" }, // Màu xanh ngọc
    ].filter((item) => item.value > 0); // Chỉ hiển thị phần tử có data
  }, [records, tConnection]);

  // Dữ liệu Biểu đồ 2: Tình trạng kết nối
  const statusStats = useMemo(() => {
    const statusMap: Record<string, { name: string; color: string }> = {
      accepted: { name: tConnection("accepted"), color: "#3b82f6" }, // Xanh dương
      pending: { name: tConnection("pending"), color: "#f59e0b" }, // Vàng cam
      rejected: { name: tConnection("rejected"), color: "#ef4444" }, // Đỏ
      cancelled: { name: tConnection("cancelled"), color: "#9ca3af" }, // Xám
      blocked: { name: tConnection("blocked"), color: "#1f2937" }, // Đen xám
    };

    const stats = Object.keys(statusMap).map((key) => ({
      name: statusMap[key].name,
      value: records.filter((r) => r.status === key).length,
      color: statusMap[key].color,
    }));

    return stats.filter((item) => item.value > 0);
  }, [records, tConnection]);

  // Xử lý Xoá lọc
  const handleClearFilters = () => {
    setParams(defaultParams);
  };

  return (
    <Space orientation="vertical" size="large" style={{ width: "100%" }}>
      <Form>
        <Flex gap="middle" align="flex-start" wrap="wrap">
          <Space orientation="vertical">
            <Form.Item label={tCommon("status")}>
              <Select
                style={{ width: 180 }}
                value={params.status}
                onChange={(val) => setParams({ ...params, status: val })}
                options={[
                  { label: tCommon("all"), value: "all" },
                  { label: tConnection("accepted"), value: "accepted" },
                  { label: tConnection("pending"), value: "pending" },
                  { label: tConnection("rejected"), value: "rejected" },
                  { label: tConnection("cancelled"), value: "cancelled" },
                  { label: tConnection("blocked"), value: "blocked" },
                ]}
                placeholder={tCommon("select_status")}
              />
            </Form.Item>
          </Space>

          <Space orientation="vertical" size="small">
            <Form.Item label={tConnection("filter_type")}>
              <Select
                style={{ width: 180 }}
                value={params.type}
                onChange={(val) => setParams({ ...params, type: val })}
                options={[
                  { label: tCommon("all"), value: "all" },
                  { label: tConnection("received"), value: "received" },
                  { label: tConnection("sent"), value: "sent" },
                ]}
                placeholder={tConnection("filter_type")}
              />
            </Form.Item>
          </Space>

          <Space orientation="vertical" size="small">
            <Form.Item label={tConnection("filter_account_type")}>
              <Select
                style={{ width: 180 }}
                value={params.account_type || "all"}
                onChange={(val) =>
                  setParams({
                    ...params,
                    account_type:
                      val === "all"
                        ? undefined
                        : (val as
                            | "farmer"
                            | "purchaser"
                            | "trader"
                            | "company"),
                  })
                }
                options={[
                  { label: tCommon("all"), value: "all" },
                  { label: tRegister("farmer"), value: "farmer" },
                  { label: tRegister("purchaser"), value: "purchaser" },
                  { label: tRegister("transport"), value: "transport" },
                  { label: tRegister("factory"), value: "factory" },
                  { label: tRegister("sales"), value: "sales" },
                ]}
                placeholder={tConnection("filter_account_type")}
              />
            </Form.Item>
          </Space>

          <Button onClick={handleClearFilters}>
            {tConnection("clear_filter")}
          </Button>
        </Flex>
      </Form>

      <Spin spinning={isFetching}>
        <Row gutter={[24, 24]}>
          {/* Chart 1 */}
          <Col xs={24} lg={12}>
            <Card
              title={
                <div style={{ textAlign: "center", fontSize: 18 }}>
                  {tConnection("statistics_direction")}
                </div>
              }
              style={{
                borderRadius: 12,
                boxShadow: "0 1px 4px rgba(0,0,0,0.1)",
              }}>
              {directionStats.length > 0 ? (
                <div style={{ height: 350, width: "100%" }}>
                  <Pie
                    data={directionStats}
                    angleField="value"
                    colorField="name"
                    radius={0.8}
                    innerRadius={0.5} // Donut chart for premium look
                    label={{
                      text: "value",
                      position: "outside",
                      style: {
                        fontWeight: "bold",
                      },
                    }}
                    legend={{
                      color: {
                        position: "right",
                        layout: "vertical",
                      },
                    }}
                    scale={{
                      color: {
                        range: ["#3b82f6", "#4ade80"],
                      },
                    }}
                    tooltip={{
                      title: "name",
                      items: [{ channel: "y", name: tCommon("total") }],
                    }}
                  />
                </div>
              ) : (
                <Empty
                  description={tCommon("no_data")}
                  style={{
                    height: 300,
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "center",
                  }}
                />
              )}
            </Card>
          </Col>

          {/* Chart 2 */}
          <Col xs={24} lg={12}>
            <Card
              title={
                <div style={{ textAlign: "center", fontSize: 18 }}>
                  {tConnection("statistics_status")}
                </div>
              }
              style={{
                borderRadius: 12,
                boxShadow: "0 1px 4px rgba(0,0,0,0.1)",
              }}>
              {statusStats.length > 0 ? (
                <div style={{ height: 350, width: "100%" }}>
                  <Pie
                    data={statusStats}
                    angleField="value"
                    colorField="name"
                    radius={0.8}
                    innerRadius={0.5}
                    label={{
                      text: "value",
                      position: "outside",
                      style: {
                        fontWeight: "bold",
                      },
                    }}
                    legend={{
                      color: {
                        position: "right",
                        layout: "vertical",
                      },
                    }}
                    scale={{
                      color: {
                        range: statusStats.map((s) => s.color),
                      },
                    }}
                    tooltip={{
                      title: "name",
                      items: [{ channel: "y", name: tCommon("total") }],
                    }}
                  />
                </div>
              ) : (
                <Empty
                  description={tCommon("no_data")}
                  style={{
                    height: 300,
                    display: "flex",
                    flexDirection: "column",
                    justifyContent: "center",
                  }}
                />
              )}
            </Card>
          </Col>
        </Row>
      </Spin>
    </Space>
  );
};

export default StaticsConnect;
