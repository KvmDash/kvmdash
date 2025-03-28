import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import { Card, CardContent, CardHeader, Box, LinearProgress, Alert, Tabs, Tab } from "@mui/material";
import { BarChart, Bar, LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer, Cell } from 'recharts';

import { CpuData } from '@interfaces/host.types';
import { getCpuInfo } from '@services/host';

/**
 * Bestimmt die Farbe basierend auf der CPU-Auslastung
 */
const getUsageColor = (usage: number): string => {
    if (usage > 80) return '#ff4444';     // Rot bei hoher Last
    if (usage > 60) return '#ffaa00';     // Orange bei mittlerer Last
    return '#00c853';                     // Grün bei niedriger Last
};

interface HistoricalCpuData {
    timestamp: number;
    cores: {
        [key: string]: number;
    };
}

// Am Anfang der Datei nach den anderen Interfaces:
interface LineChartDataPoint {
    timestamp: string;
    [key: string]: string | number;  // Für dynamische Core-Namen
}

// Define interfaces for chart data
interface CpuChartDataEntry {
    name: string;
    usage: number;
    color: string;
}

// Define interface for tooltip props
interface TooltipProps {
    active?: boolean;
    payload?: {
        name: string;
        value: number;
        payload?: CpuChartDataEntry;
        color?: string;
        stroke?: string;
        dataKey?: string;
    }[];
    label?: string | number | null;
}

export const CpuInfo = () => {
    const { t } = useTranslation();
    const [cpuData, setCpuData] = useState<CpuData[]>([]);
    const [historicalData, setHistoricalData] = useState<HistoricalCpuData[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState(0);

    useEffect(() => {
        const fetchCpuInfo = async () => {
            try {
                const data = await getCpuInfo();
                setCpuData(data);
                
                // Historische Daten aktualisieren (max. 60 Datenpunkte für 5 Minuten Historie)
                setHistoricalData(prev => {
                    const coresData: {[key: string]: number} = {};
                    data.forEach(cpu => {
                        coresData[cpu.cpu] = cpu.usage;
                    });
                    
                    const newData = [...prev, {
                        timestamp: Date.now(),
                        cores: coresData
                    }];
                    
                    // Begrenze Datenpunkte auf 60 (5 Minuten bei 5-Sekunden-Intervall)
                    return newData.slice(-60);
                });
                
                setError(null);
            } catch (err) {
                setError(err instanceof Error ? err.message : t('host.unknownError'));
                console.error(t('host.cpuInfoLoadError'), err);
            } finally {
                setLoading(false);
            }
        };

        fetchCpuInfo();
        
        // Aktualisierung alle 5 Sekunden
        const interval = setInterval(fetchCpuInfo, 5000);
        return () => clearInterval(interval);
    }, [t]);

    const handleTabChange = (_: React.SyntheticEvent, newValue: number) => {
        setActiveTab(newValue);
    };

    // Daten für das Balkendiagramm aufbereiten
    const barChartData = cpuData.map(cpu => ({
        name: cpu.cpu === 'cpu' ? t('host.cpu.total') : `${t('host.cpu.core')} ${cpu.cpu.replace('cpu', '')}`,
        usage: cpu.usage,
        color: getUsageColor(cpu.usage)
    }));

    // Daten für das Liniendiagramm aufbereiten
    const lineChartData = historicalData.map(data => {
        const result: LineChartDataPoint = { 
            timestamp: new Date(data.timestamp).toLocaleTimeString() 
        };
        Object.keys(data.cores).forEach(core => {
            const name = core === 'cpu' ? t('host.cpu.total') : `${t('host.cpu.core')} ${core.replace('cpu', '')}`;
            result[name] = data.cores[core];
        });
        return result;
    });

    // Farbpalette für die Linien
    const getLineColor = (index: number) => {
        const colors = ['#8884d8', '#82ca9d', '#ffc658', '#ff8042', '#0088FE', '#00C49F', '#FFBB28', '#FF8042'];
        return colors[index % colors.length];
    };

    // Alle Core-Namen extrahieren
    const coreNames = cpuData
        .map(cpu => cpu.cpu === 'cpu' ? t('host.cpu.total') : `${t('host.cpu.core')} ${cpu.cpu.replace('cpu', '')}`)
        .sort((a, b) => {
            if (a === t('host.cpu.total')) return -1;
            if (b === t('host.cpu.total')) return 1;
            
            // Numerische Sortierung statt lexikographischer Sortierung
            const numA = parseInt(a.replace(`${t('host.cpu.core')} `, ''), 10);
            const numB = parseInt(b.replace(`${t('host.cpu.core')} `, ''), 10);
            
            return numA - numB;
        });

    // Benutzerdefinierter Tooltip mit angepasstem Styling
    const CustomTooltip = ({ active, payload, label }: TooltipProps) => {
        if (active && payload && payload.length) {
            return (
                <div style={{ 
                    backgroundColor: 'rgba(40, 40, 40, 0.9)', 
                    padding: '8px', 
                    border: '1px solid #444',
                    borderRadius: '5px',
                    color: 'white',
                    fontSize: '12px' // Kleinere Schriftgröße
                }}>
                    {/* Zeit nur anzeigen, wenn wir im ersten Tab (Aktuelle Auslastung) sind */}
                    {activeTab === 0 && label && <p style={{ margin: '2px 0' }}>{`${label}`}</p>}
                    
                    {payload.map((entry, index) => (
                        <p key={`item-${index}`} style={{ color: entry.color || entry.stroke, margin: '2px 0' }}>
                            {`${entry.name || entry.dataKey}: ${parseFloat(entry.value.toString()).toFixed(1)}%`}
                        </p>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <Box sx={{ flexGrow: 1, p: 2 }}>
            <Card elevation={3} sx={{ borderRadius: 3, minHeight: 450 }}>
                <CardHeader 
                    title={t('host.cpu.utilization')}
                    action={
                        <Tabs value={activeTab} onChange={handleTabChange}>
                            <Tab label={t('host.cpu.current')} />
                            <Tab label={t('host.cpu.history')} />
                        </Tabs>
                    }
                />
                <CardContent>
                    {loading ? (
                        <Box display="flex" justifyContent="center" p={2}>
                            <LinearProgress />
                        </Box>
                    ) : error ? (
                        <Alert severity="error">{error}</Alert>
                    ) : (
                        <>
                            {activeTab === 0 ? (
                                // Balkendiagramm für aktuelle Auslastung
                                <Box sx={{ 
                                    width: '100%', 
                                    height: 350,
                                    '& .recharts-wrapper': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-surface': {
                                        backgroundColor: 'transparent',
                                    }
                                }}>
                                    <ResponsiveContainer>
                                        <BarChart
                                            data={barChartData}
                                            layout="vertical"
                                            margin={{ top: 20, right: 30, left: 50, bottom: 5 }}
                                            style={{ backgroundColor: 'transparent' }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" stroke="#444" />
                                            <XAxis type="number" domain={[0, 100]} tickCount={11} unit="%" />
                                            <YAxis dataKey="name" type="category" />
                                            <Tooltip 
                                                content={<CustomTooltip />}
                                                cursor={{ fill: 'rgba(50, 50, 50, 0.3)' }}
                                            />
                                            <Legend />
                                            <Bar 
                                                dataKey="usage" 
                                                name={t('host.cpu.usage')} 
                                                fill="#8884d8" 
                                                background={{ fill: '#3f3f3f' }}
                                                animationDuration={300}
                                                // Farbgebung je nach Auslastung
                                                isAnimationActive={true}
                                                label={{ position: 'right', formatter: (value: number) => `${value.toFixed(1)}%` }}
                                            >
                                                {barChartData.map((entry, index) => (
                                                    <Cell key={`cell-${index}`} fill={entry.color} />
                                                ))}
                                            </Bar>
                                        </BarChart>
                                    </ResponsiveContainer>
                                </Box>
                            ) : (
                                // Liniendiagramm für den Verlauf
                                <Box sx={{ 
                                    width: '100%', 
                                    height: 350,
                                    '& .recharts-wrapper': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-surface': {
                                        backgroundColor: 'transparent',
                                    }
                                }}>
                                    <ResponsiveContainer>
                                        <LineChart
                                            data={lineChartData}
                                            margin={{ top: 20, right: 30, left: 20, bottom: 20 }}
                                            style={{ backgroundColor: 'transparent' }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" stroke="#444" />
                                            <XAxis dataKey="timestamp" label={{ value: t('host.cpu.time'), position: 'insideBottomRight', offset: -5 }} />
                                            <YAxis domain={[0, 100]} tickCount={11} label={{ value: t('host.cpu.usagePercent'), angle: -90, position: 'insideLeft' }} />
                                            <Tooltip 
                                                content={<CustomTooltip />}
                                                cursor={{ stroke: 'rgba(50, 50, 50, 0.3)', strokeWidth: 2 }}
                                            />
                                            <Legend />
                                            {coreNames.map((core, index) => (
                                                <Line 
                                                    key={core}
                                                    type="monotone" 
                                                    dataKey={core} 
                                                    stroke={getLineColor(index)}
                                                    dot={false} 
                                                    activeDot={{ r: 5 }}
                                                    animationDuration={300}
                                                    isAnimationActive={true}
                                                />
                                            ))}
                                        </LineChart>
                                    </ResponsiveContainer>
                                </Box>
                            )}
                        </>
                    )}
                </CardContent>
            </Card>
        </Box>
    );
};

export default CpuInfo;