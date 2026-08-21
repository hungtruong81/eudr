"use client";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import {
  Button,
  Card,
  Col,
  Collapse,
  Empty,
  Flex,
  message,
  Row,
  Spin,
  Table,
  Tag,
  Typography,
} from "antd";
import { getTransportationRouteByCode } from "@/features/route/transportation-route/actions";
import { getRawMaterialTank } from "@/features/factory/factory-metadata/raw-material-tank/actions";
import { ITransportationRoute } from "@/features/route/transportation-route/types";
import {
  ArrowLeftOutlined,
  DatabaseOutlined,
  CheckCircleFilled,
} from "@ant-design/icons";
import dayjs from "dayjs";
import { useState } from "react";
import UnloadAllocationModal from "./unload-allocation-modal";
import { useTranslations } from "next-intl";

import RssProcessing from "../../rss-processing/components/rss-processing";
import { QrScannerInput } from "../../rss-processing/components/shared/qr-scanner-input";

const { Title, Text } = Typography;
const { Panel } = Collapse;

interface UnloadedReceiveMaterialTankProps {
  transportationRoute: ITransportationRoute;
  onBack: () => void;
  isTab?: boolean;
  onUnloadSuccess?: () => void;
}

const UnloadedReceiveMaterialTank = ({
  transportationRoute,
  onBack,
  isTab = false,
  onUnloadSuccess,
}: UnloadedReceiveMaterialTankProps) => {
  const t = useTranslations("Factory.receive_material");
  const tc = useTranslations("Common");
  const tf = useTranslations("Factory.fg_receipt");
  const tm = useTranslations("Factory.metadata.raw_material_tank");

  const [selectedTanks, setSelectedTanks] = useState<any[]>([]);
  const [openAllocation, setOpenAllocation] = useState(false);
  const [showRssProcessing, setShowRssProcessing] = useState(false);
  const queryClient = useQueryClient();

  const { data: routeDetail, isLoading: isLoadingRoute } = useQuery({
    queryKey: [
      "transportation-route-detail",
      transportationRoute.transportation_route_code,
    ],
    queryFn: () =>
      getTransportationRouteByCode(
        transportationRoute.transportation_route_code,
      ),
  });

  const { data: tanks, isLoading: isLoadingTanks } = useQuery({
    queryKey: [
      "raw-material-tanks",
      transportationRoute.destination_factory_id,
    ],
    queryFn: () =>
      getRawMaterialTank({
        page: 1,
        limit: 100,
        factory_id: transportationRoute.destination_factory_id,
      }),
  });

  const detail = routeDetail?.data;

  const detailColumns = [
    {
      title: tc("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tc("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const types: Record<string, string> = {
    latex: tc("latex"),
    scrap_rubber: tc("scrap_rubber"),
    mixed: tc("mixed"),
  };

  const mapColor: Record<string, string> = {
    latex: "blue",
    scrap_rubber: "orange",
    mixed: "green",
  };

  const toggleTankSelection = (tank: any) => {
    const isSelected = selectedTanks.some(
      (t) => t.raw_material_tank_id === tank.raw_material_tank_id,
    );
    if (isSelected) {
      setSelectedTanks(
        selectedTanks.filter(
          (t) => t.raw_material_tank_id !== tank.raw_material_tank_id,
        ),
      );
    } else {
      setSelectedTanks([...selectedTanks, tank]);
    }
  };

  const handleQrScan = (value: string) => {
    const foundTank = tanks?.data?.records?.find(
      (t) =>
        t.raw_material_tank_name.toLowerCase() === value.toLowerCase() ||
        String(t.raw_material_tank_id) === value,
    );
    if (foundTank) {
      toggleTankSelection(foundTank);
      message.success(
        `Đã quét và chọn bồn: ${foundTank.raw_material_tank_name}`,
      );
    } else {
      message.error(`Không tìm thấy bồn có mã: ${value}`);
    }
  };

  if (showRssProcessing) {
    return (
      <RssProcessing
        transportationRoute={transportationRoute}
        onBack={() => setShowRssProcessing(false)}
      />
    );
  }

  return (
    <div style={{ display: "flex", flexDirection: "column", gap: "24px" }}>
      {isTab ? (
        <Flex justify="space-between" align="center">
          <Title level={4} style={{ margin: 0 }}>
            {t("unload_to_tank_tooltip")}
          </Title>
          {selectedTanks.length > 0 && (
            <Button
              type="primary"
              size="large"
              icon={<DatabaseOutlined />}
              onClick={() => setOpenAllocation(true)}>
              {t("alloc_weight_for_tanks", { count: selectedTanks.length })}
            </Button>
          )}
        </Flex>
      ) : (
        <Flex align="center" justify="space-between">
          <Flex align="center" gap="middle">
            <Button icon={<ArrowLeftOutlined />} onClick={onBack}>
              {tc("back")}
            </Button>
            <Title level={4} style={{ margin: 0 }}>
              {t("unload_to_tank_tooltip")}
            </Title>
          </Flex>
          <Flex gap="small">
            <Button
              onClick={() => setShowRssProcessing(true)}
              style={{ borderColor: "#1890ff", color: "#1890ff" }}>
              Chế biến mủ tờ (RSS)
            </Button>
            {selectedTanks.length > 0 && (
              <Button
                type="primary"
                size="large"
                icon={<DatabaseOutlined />}
                onClick={() => setOpenAllocation(true)}>
                {t("alloc_weight_for_tanks", { count: selectedTanks.length })}
              </Button>
            )}
          </Flex>
        </Flex>
      )}

      <Collapse
        defaultActiveKey={["1"]}
        items={[
          {
            key: "1",
            label: t("route_info"),
            children: (
              <Spin spinning={isLoadingRoute}>
                {detail ? (
                  <Table
                    dataSource={[
                      {
                        label: t("route_code"),
                        value: detail.transportation_route_code?.toUpperCase(),
                      },
                      {
                        label: tc("vehicle"),
                        value: `${detail.vehicle_name} (${detail.vehicle_license_plate})`,
                      },
                      { label: t("driver"), value: detail.driver_name },
                      {
                        label: t("transport_date"),
                        value: dayjs(detail.transport_date).format(
                          "DD/MM/YYYY",
                        ),
                      },
                      { label: t("pickup_time"), value: detail.pickup_time },
                      {
                        label: t("destination_factory"),
                        value: detail.destination_factory_name,
                      },
                    ]}
                    columns={detailColumns}
                    pagination={false}
                    size="small"
                    bordered
                    rowKey="label"
                  />
                ) : (
                  <Empty />
                )}
              </Spin>
            ),
          },
        ]}
      />
      <div>
        <Flex
          justify="space-between"
          align="center"
          style={{ marginBottom: "16px" }}>
          <Title level={5} style={{ margin: 0 }}>
            {tm("title_list")}
          </Title>
          <div style={{ width: "300px" }}>
            <QrScannerInput
              placeholder="Quét QR Bồn chứa..."
              onScan={handleQrScan}
            />
          </div>
        </Flex>
        <Spin spinning={isLoadingTanks}>
          <Row gutter={[16, 16]}>
            {tanks?.data?.records?.map((tank) => {
              const isSelected = selectedTanks.some(
                (t) => t.raw_material_tank_id === tank.raw_material_tank_id,
              );
              return (
                <Col
                  key={tank.raw_material_tank_id}
                  xs={24}
                  sm={12}
                  md={8}
                  lg={6}>
                  <Card
                    hoverable
                    style={{
                      border: isSelected ? "2px solid #1890ff" : undefined,
                      position: "relative",
                    }}
                    onClick={() => toggleTankSelection(tank)}>
                    {isSelected && (
                      <CheckCircleFilled
                        style={{
                          position: "absolute",
                          top: -10,
                          right: -10,
                          fontSize: "24px",
                          color: "#1890ff",
                          background: "#fff",
                          borderRadius: "50%",
                        }}
                      />
                    )}
                    <Card.Meta
                      title={
                        <>
                          <Flex justify="space-between" align="center">
                            <Text>{tank.raw_material_tank_name}</Text>
                            <Tag color={mapColor[tank.tank_type]}>
                              {types[tank.tank_type]}
                            </Tag>
                          </Flex>
                        </>
                      }
                      description={
                        <div
                          style={{ display: "flex", flexDirection: "column" }}>
                          <Text type="secondary">
                            {tc("factory")}: {tank.factory_name}
                          </Text>
                          <Text>
                            {tc("location")}: {tank.location}
                          </Text>
                          <Text>
                            {tc("capacity")}: {tank.current_volume}/
                            {tank.capacity} kg
                          </Text>
                        </div>
                      }
                    />
                  </Card>
                </Col>
              );
            })}
            {!isLoadingTanks && tanks?.data?.records?.length === 0 && (
              <Col span={24}>
                <Empty description={tm("empty_at_factory")} />
              </Col>
            )}
          </Row>
        </Spin>
      </div>

      <UnloadAllocationModal
        open={openAllocation}
        onClose={() => setOpenAllocation(false)}
        transportationRoute={detail!}
        selectedTanks={selectedTanks}
        onSuccess={() => {
          queryClient.invalidateQueries({
            queryKey: ["transportation-routes-receive"],
          });
          queryClient.invalidateQueries({ queryKey: ["raw-material-tanks"] });
          if (onUnloadSuccess) {
            onUnloadSuccess();
          } else {
            onBack();
          }
        }}
      />
    </div>
  );
};

export default UnloadedReceiveMaterialTank;
