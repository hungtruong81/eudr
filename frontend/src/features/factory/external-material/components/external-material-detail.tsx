"use client";
import React, { useCallback, useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { GoogleMap, InfoWindow, Polygon } from "@react-google-maps/api";
import {
  Card,
  Col,
  Empty,
  Row,
  Skeleton,
  Space,
  Tabs,
  Tag,
  Typography,
  Table,
} from "antd";
import {
  CarOutlined,
  EnvironmentOutlined,
  FileTextOutlined,
  GlobalOutlined,
} from "@ant-design/icons";
import { getExternalMaterialByCode } from "../actions";
import BaseSheet from "@/components/shared/base-sheet";
import dayjs from "dayjs";
import { IExternalMaterial, IExternalMaterialLand } from "../types";
import { useTranslations } from "next-intl";

const { Text } = Typography;

interface Props {
  open: boolean;
  onClose: () => void;
  externalMaterialCode: string | null;
}

const mapContainerStyle = {
  width: "100%",
  height: "70vh",
};

const defaultCenter = {
  lat: 16.047079,
  lng: 108.20623,
};

const ExternalMaterialDetail = ({
  open,
  onClose,
  externalMaterialCode,
}: Props) => {
  const t = useTranslations("Factory.external_material");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");
  const tMap = useTranslations("ManageLand.Map");

  const [selectedPlot, setSelectedPlot] =
    useState<IExternalMaterialLand | null>(null);
  const [selectedPosition, setSelectedPosition] = useState<{
    lat: number;
    lng: number;
  } | null>(null);

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

  const { data: response, isLoading } = useQuery({
    queryKey: ["external-material-detail", externalMaterialCode],
    queryFn: () => getExternalMaterialByCode(externalMaterialCode!),
    enabled: !!externalMaterialCode && open,
  });

  const record: IExternalMaterial | undefined = response?.data;

  const mapStatusColor: Record<string, string> = {
    pending: "warning",
    draft: "default",
    confirmed: "success",
    cancelled: "error",
  };

  const mapStatusText: Record<string, string> = {
    pending: ts("pending"),
    draft: ts("draft"),
    confirmed: ts("confirmed"),
    cancelled: ts("cancelled"),
  };

  const renderGeneralInfo = (data: IExternalMaterial) => (
    <div style={{ display: "flex", flexDirection: "column", gap: "24px" }}>
      <Table
        dataSource={[
          {
            label: t("code"),
            value: data.external_material_code?.toUpperCase(),
          },
          {
            label: t("status"),
            value: (
              <Tag color={mapStatusColor[data.status] || "default"}>
                {mapStatusText[data.status] || data.status}
              </Tag>
            ),
          },
          {
            label: t("factory_receive"),
            value: data.factory_name,
          },
          {
            label: t("supplier_name"),
            value: data.supplier_name,
          },
          {
            label: t("supplier_phone"),
            value: data.supplier_phone,
          },
          {
            label: t("purchase_date"),
            value: dayjs(data.purchase_date).format("DD/MM/YYYY"),
          },
          {
            label: t("supplier_address"),
            value: data.supplier_address,
          },
          {
            label: t("order_notes"),
            value: data.notes,
          },
        ]}
        columns={detailColumns}
        pagination={false}
        size="small"
        bordered
        rowKey="label"
      />

      <Row gutter={16}>
        <Col span={12}>
          <Table
            title={() => <Text strong>{t("water_latex")}</Text>}
            dataSource={[
              {
                label: t("weight"),
                value: `${Number(data.latex_weight).toLocaleString("vi-VN")} kg`,
              },
              {
                label: t("tsc_grade"),
                value: `${data.latex_tsc_grade}%`,
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
          />
        </Col>
        <Col span={12}>
          <Table
            title={() => <Text strong>{t("scrap_rubber")}</Text>}
            dataSource={[
              {
                label: t("weight"),
                value: `${Number(data.scrap_rubber_weight).toLocaleString("vi-VN")} kg`,
              },
              {
                label: t("drc_grade"),
                value: `${data.scrap_rubber_drc_grade}%`,
              },
            ]}
            columns={detailColumns}
            pagination={false}
            size="small"
            bordered
            rowKey="label"
          />
        </Col>
      </Row>

      <Table
        dataSource={[
          {
            label: t("total_value"),
            value: (
              <Text strong style={{ color: "#f5222d", fontSize: "16px" }}>
                {Number(data.total_amount).toLocaleString("vi-VN")}{" "}
                {t("currency_vnd")}
              </Text>
            ),
          },
        ]}
        columns={detailColumns}
        pagination={false}
        size="small"
        bordered
        rowKey="label"
      />
    </div>
  );

  const renderLandsInfo = (lands: IExternalMaterialLand[]) => (
    <Row gutter={[24, 24]}>
      <Col xs={24} lg={24}>
        {renderMap(lands)}
      </Col>
      <Col xs={24} lg={24}>
        <div
          style={{
            display: "flex",
            flexDirection: "column",
            gap: "20px",
            maxHeight: "72vh",
            overflowY: "auto",
            paddingRight: "8px",
          }}>
          {lands && lands.length > 0 ? (
            lands.map((land: IExternalMaterialLand) => (
              <Card
                key={land.external_material_land_id}
                size="small"
                title={
                  <Space>
                    <EnvironmentOutlined />
                    <span>{t("lot_number", { index: land.plot_name })}</span>
                  </Space>
                }
                extra={<Tag color="cyan">{land.plot_code?.toUpperCase()}</Tag>}>
                <Table
                  dataSource={[
                    {
                      label: t("plot_area"),
                      value: `${land.land_area} ha`,
                    },
                    {
                      label: t("expected_yield"),
                      value: `${Number(land.harvest_weight).toLocaleString("vi-VN")} kg`,
                    },
                    {
                      label: t("province"),
                      value: land.province_name,
                    },
                    {
                      label: t("details_address"),
                      value: land.address,
                    },
                    {
                      label: t("plot_notes"),
                      value: land.notes,
                    },
                  ]}
                  columns={detailColumns}
                  pagination={false}
                  size="small"
                  bordered
                  rowKey="label"
                />
              </Card>
            ))
          ) : (
            <Empty description={t("no_plots_info")} />
          )}
        </div>
      </Col>
    </Row>
  );
  const renderTransportInfo = (data: IExternalMaterial) => (
    <Table
      dataSource={[
        {
          label: t("vehicle_plate"),
          value: data.transport?.vehicle_license_plate ? (
            <Tag color="blue">
              {data.transport.vehicle_license_plate.toUpperCase()}
            </Tag>
          ) : (
            "—"
          ),
        },
        { label: t("driver_name"), value: data.transport?.driver_name },
        { label: t("driver_phone"), value: data.transport?.driver_phone },
        {
          label: t("transport_date"),
          value: data.transport?.transport_date
            ? dayjs(data.transport.transport_date).format("DD/MM/YYYY")
            : "—",
        },
        { label: t("pickup_time"), value: data.transport?.pickup_time },
        { label: t("delivery_time"), value: data.transport?.delivery_time },
        {
          label: t("pickup_location"),
          value: data.transport?.pickup_location,
        },
        { label: tc("notes"), value: data.transport?.notes },
      ]}
      columns={detailColumns}
      pagination={false}
      size="small"
      bordered
      rowKey="label"
    />
  );

  const handlePolygonClick = (
    plot: IExternalMaterialLand,
    event: google.maps.MapMouseEvent,
  ) => {
    if (event.latLng) {
      setSelectedPosition({ lat: event.latLng.lat(), lng: event.latLng.lng() });
      setSelectedPlot(plot);
    }
  };

  const onLoadMap = useCallback(
    (map: google.maps.Map) => {
      if (record?.lands && record.lands.length > 0) {
        const bounds = new window.google.maps.LatLngBounds();
        let hasCoordinates = false;

        record.lands.forEach((plot) => {
          if (plot.coordinates && plot.coordinates.length > 0) {
            hasCoordinates = true;
            plot.coordinates.forEach((coord) => {
              bounds.extend(
                new window.google.maps.LatLng(coord.lat, coord.lng),
              );
            });
          }
        });

        if (hasCoordinates) {
          map.fitBounds(bounds);
        }
      }
    },
    [record?.lands],
  );

  const renderMap = (lands: IExternalMaterialLand[]) => (
    <div
      style={{
        border: "1px solid #f0f0f0",
        borderRadius: "8px",
        overflow: "hidden",
      }}>
      <GoogleMap
        mapContainerStyle={mapContainerStyle}
        center={defaultCenter}
        zoom={6}
        onLoad={onLoadMap}
        options={{ mapTypeId: "satellite" }}>
        {lands.map((plot) => {
          if (!plot.coordinates || plot.coordinates.length < 3) return null;

          return (
            <Polygon
              key={plot.external_material_land_id}
              paths={plot.coordinates}
              options={{
                fillColor: "#52c41a",
                fillOpacity: 0.4,
                strokeColor: "#389e0d",
                strokeOpacity: 0.8,
                strokeWeight: 2,
              }}
              onClick={(e) => handlePolygonClick(plot, e)}
            />
          );
        })}

        {selectedPlot && selectedPosition && (
          <InfoWindow
            position={selectedPosition}
            onCloseClick={() => {
              setSelectedPlot(null);
              setSelectedPosition(null);
            }}>
            <div style={{ minWidth: 280, maxWidth: 340 }}>
              <Table
                dataSource={[
                  {
                    label: t("plot_name"),
                    value: selectedPlot.plot_name?.toUpperCase(),
                  },
                  {
                    label: t("plot_code"),
                    value: selectedPlot.plot_code?.toUpperCase(),
                  },
                  { label: tc("company"), value: selectedPlot.farmer_name },
                  {
                    label: t("plot_area"),
                    value: `${selectedPlot.land_area} ha`,
                  },
                  {
                    label: tc("address"),
                    value: selectedPlot.address || selectedPlot.province_name,
                  },
                ]}
                columns={detailColumns}
                pagination={false}
                size="small"
                bordered
                rowKey="label"
                showHeader={false}
              />
            </div>
          </InfoWindow>
        )}
      </GoogleMap>
    </div>
  );

  return (
    <BaseSheet
      title={t("details_title")}
      width={1000}
      onClose={onClose}
      open={open}>
      {isLoading ? (
        <Skeleton active paragraph={{ rows: 12 }} />
      ) : record ? (
        <Tabs
          defaultActiveKey="1"
          style={{ marginBottom: 24 }}
          items={[
            {
              key: "1",
              label: (
                <Space>
                  <FileTextOutlined />
                  <span>{t("tab_general")}</span>
                </Space>
              ),
              children: renderGeneralInfo(record),
            },
            {
              key: "2",
              label: (
                <Space>
                  <EnvironmentOutlined />
                  <span>{t("tab_plots")}</span>
                </Space>
              ),
              children: renderLandsInfo(record.lands || []),
            },
            {
              key: "3",
              label: (
                <Space>
                  <CarOutlined />
                  <span>{t("tab_transport")}</span>
                </Space>
              ),
              children: renderTransportInfo(record),
            },
          ]}
        />
      ) : (
        <Empty />
      )}
    </BaseSheet>
  );
};

export default ExternalMaterialDetail;
