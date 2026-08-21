"use client";
import { ReloadOutlined } from "@ant-design/icons";
import { GoogleMap, InfoWindow, Polygon } from "@react-google-maps/api";
import {
  Button,
  Card,
  Form,
  Select,
  Spin,
  Table,
  Tag,
  Typography,
} from "antd";
import { useTranslations } from "next-intl";
import React, { useCallback, useState } from "react";
import { getLands, IGetLandParams } from "../land/actions";
import { IPlot } from "../land/types";
import { useQuery } from "@tanstack/react-query";
import { getProvince } from "@/lib/api";

const { Title, Text } = Typography;

const mapContainerStyle = {
  width: "100%",
  height: "70vh",
};

const defaultCenter = {
  lat: 16.047079,
  lng: 108.20623,
};

const LandMap: React.FC = () => {
  const tCommon = useTranslations("Common");
  const tLand = useTranslations("ManageLand.Land");
  const tMap = useTranslations("ManageLand.Map");

  const [form] = Form.useForm();
  const [selectedPlot, setSelectedPlot] = useState<IPlot | null>(null);
  const [selectedPosition, setSelectedPosition] = useState<{
    lat: number;
    lng: number;
  } | null>(null);

  const detailColumns = [
    {
      title: tCommon("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tCommon("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const [filterParams, setFilterParams] = useState<IGetLandParams>({
    page: 1,
    limit: 100,
  });

  const { data: provinces } = useQuery({
    queryKey: ["provinces"],
    queryFn: () => getProvince(),
  });

  const {
    data: lands,
    isLoading,
  } = useQuery({
    queryKey: ["land", filterParams],
    queryFn: () => getLands(filterParams),
  });

  const handleValuesChange = (allValues: any) => {
    setFilterParams((prev) => ({
      ...prev,
      province_id: allValues.province_id,
      eudr_status: allValues.eudr_status,
      page: 1,
    }));
  };

  const handleResetFilters = () => {
    form.resetFields();
    setFilterParams({ page: 1, limit: 100 });
  };

  const handlePolygonClick = (
    plot: IPlot,
    event: google.maps.MapMouseEvent,
  ) => {
    if (event.latLng) {
      setSelectedPosition({ lat: event.latLng.lat(), lng: event.latLng.lng() });
      setSelectedPlot(plot);
    }
  };

  const onLoadMap = useCallback(
    (map: google.maps.Map) => {
      if (lands && lands?.data?.records?.length > 0) {
        const bounds = new window.google.maps.LatLngBounds();
        let hasCoordinates = false;

        lands.data.records.forEach((plot) => {
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
    [lands],
  );

  return (
    <Card>
      <Form
        form={form}
        layout="inline"
        onValuesChange={handleValuesChange}
        style={{ marginBottom: "20px" }}>
        <Form.Item name="province_id" label={tLand("province")}>
          <Select
            placeholder={tMap("select_province")}
            allowClear
            showSearch={{
              filterOption: (input, option) =>
                (option?.label ?? "")
                  .toLowerCase()
                  .includes(input.toLowerCase()),
            }}
            options={provinces?.provinces?.map((province) => ({
              label: province.province_name,
              value: province.province_id,
            }))}
          />
        </Form.Item>

        <Form.Item name="eudr_status" label={tLand("eudr_status")}>
          <Select
            placeholder={tCommon("select_status")}
            allowClear
            options={[
              { label: tLand("invalid"), value: 0 },
              { label: tLand("valid"), value: 1 },
            ]}
          />
        </Form.Item>

        <Form.Item>
          <Button icon={<ReloadOutlined />} onClick={handleResetFilters}>
            {tCommon("refresh")}
          </Button>
        </Form.Item>
      </Form>

      <Spin spinning={isLoading} description={tMap("loading_lands")}>
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
            {lands?.data?.records?.map((plot) => {
              if (!plot.coordinates || plot.coordinates.length < 3) return null;

              return (
                <Polygon
                  key={plot.plot_id}
                  paths={plot.coordinates}
                  options={{
                    fillColor: plot.eudr_status === 1 ? "#52c41a" : "#ff4d4f",
                    fillOpacity: 0.4,
                    strokeColor: plot.eudr_status === 1 ? "#389e0d" : "#cf1322",
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
                        label: tMap("plot_name"),
                        value: selectedPlot.plot_name?.toUpperCase(),
                      },
                      {
                        label: tMap("plot_code"),
                        value: selectedPlot.plot_code?.toUpperCase(),
                      },
                      { label: tMap("plantation"), value: selectedPlot.plantation_name },
                      { label: tLand("farmer_name"), value: selectedPlot.farmer_name },
                      { label: tMap("area"), value: `${selectedPlot.land_area} ha` },
                      {
                        label: tCommon("address"),
                        value:
                          selectedPlot.address || selectedPlot.province_name,
                      },
                      {
                        label: tLand("plant_type"),
                        value: selectedPlot.crop_type || selectedPlot.plant_type,
                      },
                      { label: tLand("classification"), value: selectedPlot.classify },
                      {
                        label: "EUDR",
                        value: (
                          <Tag
                            color={
                              selectedPlot.eudr_status === 1
                                ? "success"
                                : "default"
                            }>
                            {selectedPlot.eudr_status === 1
                              ? tLand("valid")
                              : tLand("invalid")}
                          </Tag>
                        ),
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
      </Spin>
    </Card>
  );
};

export default LandMap;
