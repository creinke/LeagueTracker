import React, {useState, useEffect, JSX} from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    ActivityIndicator,
    TouchableOpacity,
    ScrollView
} from 'react-native';
import EventService from '../../services/EventService';
import { EventDetail, EventResults, StandingResult, MatchupResult, PlayerMatchResult } from '../../types';
import { Ionicons } from '@expo/vector-icons';


const EventResultsScreen = ({ route }: any) => {
    const { eventId, eventNumber } = route.params;
    const [results, setResults] = useState<EventResults | null>(null);
    const [event, setEvent] = useState<EventDetail | null>(null);
    const [loading, setLoading] = useState(true);
    const [expandedTeams, setExpandedTeams] = useState<{ [key: string]: boolean }>({});

    useEffect(() => {
        loadData();
    }, [eventId]);

    const loadData = async () => {
        try {
            setLoading(true);
            const [eventData, resultsData] = await Promise.all([
                EventService.getEventDetail(eventId),
                EventService.getEventResults(eventId)
            ]);
            
            setEvent(eventData);
            setResults(resultsData);
        } catch (error) {
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    const toggleTeamExpansion = (teamId: number | string, index: number, prefix: string = '') => {
        const key = prefix ? `${prefix}-${index}` : `${teamId}-${index}`;
        setExpandedTeams(prev => ({
            ...prev,
            [key]: !prev[key]
        }));
    };


    const renderPlayerMatchResult = (player: PlayerMatchResult) => {
        const holes = Array.from({ length: 9 }, (_, i) => i + 1);
        return (
            <View style={styles.playerCard} key={player.playerName}>
                <Text style={styles.playerName}>{player.playerName} (Hcp: {player.handicap})</Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                    <View style={styles.scorecardTable}>
                        <View style={styles.scoreRow}>
                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Hole</Text></View>
                            {holes.map(h => (
                                <View key={h} style={styles.scoreCell}><Text style={styles.headerTextBlack}>{h}</Text></View>
                            ))}
                            <View style={styles.totalCell}><Text style={styles.headerTextBlack}>Total</Text></View>
                        </View>
                        <View style={styles.scoreRow}>
                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Strokes</Text></View>
                            {player.holeStrokes.map((s, idx) => (
                                <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{s}</Text></View>
                            ))}
                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.holeStrokesTotal}</Text></View>
                        </View>
                        <View style={styles.scoreRow}>
                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Points</Text></View>
                            {player.holePoints.map((p, idx) => (
                                <View key={idx} style={[styles.scoreCell, p > 0 ? styles.pointHighlight : null]}>
                                    <Text style={styles.scoreText}>{p}</Text>
                                </View>
                            ))}
                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.totalHolePoints}</Text></View>
                        </View>
                    </View>
                </ScrollView>
                <View style={styles.cardFooter}>
                    <Text style={styles.footerStat}>Net: {player.netStrokesTotal}</Text>
                    <Text style={styles.footerStat}>Net Pts: {player.netPoints}</Text>
                    <Text style={styles.footerStat}>Points: {player.totalPoints}</Text>
                </View>
            </View>
        );
    };

    const renderStandingResult = ({ item, index }: { item: StandingResult, index: number }) => {
        const isExpanded = expandedTeams[`standing-${index}`];
        const teamName = (item.teamName || '').trim();
        
        const teamMatchups = (results?.matchups || []).filter(
            (m: MatchupResult) => (m.teamOne || '').trim() === teamName || (m.teamTwo || '').trim() === teamName
        );

        return (
            <View style={styles.rowContainer}>
                <TouchableOpacity 
                    style={[styles.row, isExpanded && styles.expandedRow]} 
                    onPress={() => toggleTeamExpansion('standing', index, 'standing')}
                >
                    <View style={styles.cellRank}><Text style={styles.cellText}>{index + 1}</Text></View>
                    <View style={styles.cellName}>
                        <Text style={styles.cellText}>{item.teamName}</Text>
                    </View>
                    <View style={styles.cellPoints}><Text style={styles.cellText}>{item.points}</Text></View>
                    <View style={styles.cellTotal}><Text style={styles.cellText}>{item.totalPoints}</Text></View>
                    <View style={styles.cellBehind}><Text style={styles.cellText}>{item.pointsBehind}</Text></View>
                    <Ionicons 
                        name={isExpanded ? "chevron-up" : "chevron-down"} 
                        size={16} 
                        color="#666" 
                    />
                </TouchableOpacity>

                {isExpanded && (
                    <View style={styles.detailSection}>
                        <Text style={styles.detailHeader}>Match Details</Text>
                        {teamMatchups.map((match) => {
                            const matchKey = `${match.teamOne}-${match.teamTwo}`;
                            
                            // Try to find detailed points breakdown
                            const matchDetail = results?.teamResults?.find(tr => 
                                ((tr.teamOneName || '').trim() === (match.teamOne || '').trim() && (tr.teamTwoName || '').trim() === (match.teamTwo || '').trim()) ||
                                ((tr.teamOneName || '').trim() === (match.teamTwo || '').trim() && (tr.teamTwoName || '').trim() === (match.teamOne || '').trim())
                            );

                            // Find hole-by-hole scores
                            const currentMatchIdx = results?.matchups?.findIndex(m => m === match);
                            const matchScores = results?.teamMatches && currentMatchIdx !== undefined ? results.teamMatches[currentMatchIdx] : null;

                            return (
                                <View key={matchKey} style={styles.matchDetailItem}>
                                    <View style={styles.matchHeader}>
                                        <Text style={styles.matchTitle}>{match.teamOne} vs {match.teamTwo}</Text>
                                        <Text style={styles.matchScore}>{match.teamOnePoints} - {match.teamTwoPoints}</Text>
                                    </View>
                                    
                                    {matchDetail && (
                                        <View style={styles.pointBreakdown}>
                                            <View style={styles.breakdownRow}>
                                                <Text style={styles.breakdownLabel}>Player Points:</Text>
                                                <Text style={styles.breakdownValue}>{matchDetail.teamOnePlayerPoints} - {matchDetail.teamTwoPlayerPoints}</Text>
                                            </View>
                                            <View style={styles.breakdownRow}>
                                                <Text style={styles.breakdownLabel}>Low Net Points:</Text>
                                                <Text style={styles.breakdownValue}>{matchDetail.teamOneNetPoints} - {matchDetail.teamTwoNetPoints}</Text>
                                            </View>
                                            <View style={styles.breakdownRow}>
                                                <Text style={styles.breakdownLabel}>Total Points:</Text>
                                                <Text style={styles.breakdownValue}>{matchDetail.teamOneTotalPoints} - {matchDetail.teamTwoTotalPoints}</Text>
                                            </View>
                                        </View>
                                    )}

                                    {matchScores && Array.isArray(matchScores) && (
                                        <View style={styles.scorecardsContainer}>
                                            <Text style={styles.subHeader}>Detailed Player Scores</Text>
                                            {matchScores.map((playerArray) => 
                                                Array.isArray(playerArray) ? playerArray.map((player) => renderPlayerMatchResult(player)) : null
                                            )}
                                        </View>
                                    )}
                                </View>
                            );
                        })}
                    </View>
                )}
            </View>
        );
    };

    const renderSinglesMatchResult = ({ item, index }: { item: any, index: number }) => {
        const isExpanded = expandedTeams[`singlesMatch-${index}`];
        const playerName = (item.name || '').trim();
        
        const playerMatchups = (results?.matchups || []).filter(
            (m: MatchupResult) => (m.teamOne || '').trim() === playerName || (m.teamTwo || '').trim() === playerName
        );

        return (
            <View style={styles.rowContainer}>
                <TouchableOpacity 
                    style={[styles.row, isExpanded && styles.expandedRow]} 
                    onPress={() => toggleTeamExpansion('singlesMatch', index, 'singlesMatch')}
                >
                    <View style={styles.cellRank}><Text style={styles.cellText}>{index + 1}</Text></View>
                    <View style={styles.cellName}>
                        <Text style={styles.cellText}>{item.name}</Text>
                        {item.tieBreaker ? <Text style={styles.tieBreakerText}>Tie Breaker: {item.tieBreaker}</Text> : null}
                    </View>
                    <View style={styles.cellPoints}><Text style={styles.cellText}>{item.matchPoints}</Text></View>
                    <View style={styles.cellTotal}><Text style={styles.cellText}>{item.sessionPoints}</Text></View>
                    <View style={styles.cellBehind}><Text style={styles.cellText}>{item.seasonPoints}</Text></View>
                    <Ionicons 
                        name={isExpanded ? "chevron-up" : "chevron-down"} 
                        size={16} 
                        color="#666" 
                    />
                </TouchableOpacity>

                {isExpanded && (
                    <View style={styles.detailSection}>
                        <Text style={styles.detailHeader}>Match Details</Text>
                        {playerMatchups.map((match) => {
                            const matchKey = `${match.teamOne}-${match.teamTwo}`;
                            
                            // Find hole-by-hole scores
                            const currentMatchIdx = results?.matchups?.findIndex(m => m === match);
                            const matchScores = results?.teamMatches && currentMatchIdx !== undefined ? results.teamMatches[currentMatchIdx] : null;

                            return (
                                <View key={matchKey} style={styles.matchDetailItem}>
                                    <View style={styles.matchHeader}>
                                        <Text style={styles.matchTitle}>{match.teamOne} vs {match.teamTwo}</Text>
                                        <Text style={styles.matchScore}>{match.teamOnePoints} - {match.teamTwoPoints}</Text>
                                    </View>
                                    
                                    {matchScores && Array.isArray(matchScores) && (
                                        <View style={styles.scorecardsContainer}>
                                            <Text style={styles.subHeader}>Detailed Player Scores</Text>
                                            {matchScores.map((playerArray: any) => 
                                                Array.isArray(playerArray) ? playerArray.map((player: PlayerMatchResult) => renderPlayerMatchResult(player)) : null
                                            )}
                                        </View>
                                    )}
                                </View>
                            );
                        })}
                    </View>
                )}
            </View>
        );
    };

    const renderSinglesStrokeResult = ({ item, index }: { item: any, index: number }) => (
        <View style={styles.row}>
            <View style={styles.cellRank}><Text style={styles.cellText}>{index + 1}</Text></View>
            <View style={styles.cellName}>
                <Text style={styles.cellText}>{item.name}</Text>
                {item.tieBreaker ? <Text style={styles.tieBreakerText}>Tie Breaker: {item.tieBreaker}</Text> : null}
            </View>
            <View style={styles.cellPoints}><Text style={styles.cellText}>{item.totalScore}</Text></View>
            <View style={styles.cellTotal}><Text style={styles.cellText}>{item.totalNetScore}</Text></View>
            <View style={styles.cellBehind}><Text style={styles.cellText}>{item.sessionPoints}</Text></View>
        </View>
    );

    const renderTeamEventResult = ({ item, index }: { item: any, index: number }) => {
        const isExpanded = expandedTeams[`teamEvent-${index}`];
        const playerNames = item.players.map((p: any) => typeof p === 'object' ? (p.fullname || p.name || 'Unknown') : p).join(' / ');
        
        return (
            <View style={styles.rowContainer}>
                <TouchableOpacity 
                    style={[styles.row, isExpanded && styles.expandedRow]} 
                    onPress={() => toggleTeamExpansion('teamEvent', index, 'teamEvent')}
                >
                    <View style={styles.cellRank}><Text style={styles.cellText}>{index + 1}</Text></View>
                    <View style={styles.cellName}>
                        <Text style={styles.cellText}>{playerNames}</Text>
                        {item.tieBreaker ? <Text style={styles.tieBreakerText}>Tie Breaker: {item.tieBreaker}</Text> : null}
                    </View>
                    <View style={styles.cellPoints}><Text style={styles.cellText}>{item.gross}</Text></View>
                    <View style={styles.cellTotal}><Text style={styles.cellText}>{item.net}</Text></View>
                    <View style={styles.cellBehind}><Text style={styles.cellText}>{item.points}</Text></View>
                    <Ionicons 
                        name={isExpanded ? "chevron-up" : "chevron-down"} 
                        size={16} 
                        color="#666" 
                    />
                </TouchableOpacity>

                {isExpanded && (
                    <View style={styles.detailSection}>
                        <Text style={styles.detailHeader}>Team Member Scores</Text>
                        
                        {Array.isArray(item.players) && item.players.map((player: any, pIdx: number) => {
                            if (typeof player === 'object' && (player.firstNineScores || player.secondNineScores)) {
                                // This is a PlayerDetailResult
                                const holes = Array.from({ length: 9 }, (_, i) => i + 1);
                                const hasFirstNine = Array.isArray(player.firstNineScores) && player.firstNineScores.length > 0;
                                const hasSecondNine = Array.isArray(player.secondNineScores) && player.secondNineScores.length > 0;

                                return (
                                    <View key={pIdx} style={styles.playerCard}>
                                        <Text style={styles.playerName}>{player.name} (Hcp: {player.handicap})</Text>
                                        
                                        {hasFirstNine && (
                                            <View style={{ marginBottom: hasSecondNine && results?.secondNineName ? 15 : 0 }}>
                                                {results?.firstNineName && <Text style={styles.subHeader}>{results.firstNineName}</Text>}
                                                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                                                    <View style={styles.scorecardTable}>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Hole</Text></View>
                                                            {holes.map(h => (
                                                                <View key={h} style={styles.scoreCell}><Text style={styles.headerTextBlack}>{h}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.headerTextBlack}>Total</Text></View>
                                                        </View>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Strokes</Text></View>
                                                            {player.firstNineScores.map((s: number, idx: number) => (
                                                                <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{s}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.firstNineTotalScore}</Text></View>
                                                        </View>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Net</Text></View>
                                                            {player.firstNineNetScores.map((n: number, idx: number) => (
                                                                <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{n}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.firstNineTotalNetScore}</Text></View>
                                                        </View>
                                                    </View>
                                                </ScrollView>
                                            </View>
                                        )}

                                        {hasSecondNine && (
                                            <View>
                                                {results?.secondNineName && <Text style={styles.subHeader}>{results.secondNineName}</Text>}
                                                <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                                                    <View style={styles.scorecardTable}>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Hole</Text></View>
                                                            {holes.map(h => (
                                                                <View key={h} style={styles.scoreCell}><Text style={styles.headerTextBlack}>{h}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.headerTextBlack}>Total</Text></View>
                                                        </View>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Strokes</Text></View>
                                                            {player.secondNineScores.map((s: number, idx: number) => (
                                                                <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{s}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.secondNineTotalScore}</Text></View>
                                                        </View>
                                                        <View style={styles.scoreRow}>
                                                            <View style={styles.labelCell}><Text style={styles.scoreLabel}>Net</Text></View>
                                                            {player.secondNineNetScores.map((n: number, idx: number) => (
                                                                <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{n}</Text></View>
                                                            ))}
                                                            <View style={styles.totalCell}><Text style={styles.totalText}>{player.secondNineTotalNetScore}</Text></View>
                                                        </View>
                                                    </View>
                                                </ScrollView>
                                            </View>
                                        )}
                                    </View>
                                );
                            } else {
                                // Fallback for simple string player names or missing details
                                return (
                                    <View key={pIdx} style={styles.matchDetailItem}>
                                        <Text style={styles.playerName}>{typeof player === 'object' ? player.name : player}</Text>
                                    </View>
                                );
                            }
                        })}

                        {results?.format === 'SHAMBLE' && (
                            <View style={styles.playerCard}>
                                <Text style={styles.playerName}>Team Best Hole Score</Text>
                                {Array.isArray(item.firstNineScores) && item.firstNineScores.length > 0 && (
                                    <View style={{ marginBottom: results?.secondNineName ? 15 : 0 }}>
                                        {results?.firstNineName && <Text style={styles.subHeader}>{results.firstNineName}</Text>}
                                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                                            <View style={styles.scorecardTable}>
                                                <View style={styles.scoreRow}>
                                                    <View style={styles.labelCell}><Text style={styles.scoreLabel}>Hole</Text></View>
                                                    {Array.from({ length: 9 }, (_, i) => i + 1).map(h => (
                                                        <View key={h} style={styles.scoreCell}><Text style={styles.headerTextBlack}>{h}</Text></View>
                                                    ))}
                                                    <View style={styles.totalCell}><Text style={styles.headerTextBlack}>Total</Text></View>
                                                </View>
                                                <View style={styles.scoreRow}>
                                                    <View style={styles.labelCell}><Text style={styles.scoreLabel}>Best Net</Text></View>
                                                    {item.firstNineNetScores.map((n: number, idx: number) => (
                                                        <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{n}</Text></View>
                                                    ))}
                                                    <View style={styles.totalCell}><Text style={styles.totalText}>{item.firstNineTotalNetScore}</Text></View>
                                                </View>
                                            </View>
                                        </ScrollView>
                                    </View>
                                )}
                                {Array.isArray(item.secondNineScores) && item.secondNineScores.length > 0 && results?.secondNineName && (
                                    <View>
                                        {results?.secondNineName && <Text style={styles.subHeader}>{results.secondNineName}</Text>}
                                        <ScrollView horizontal showsHorizontalScrollIndicator={false}>
                                            <View style={styles.scorecardTable}>
                                                <View style={styles.scoreRow}>
                                                    <View style={styles.labelCell}><Text style={styles.scoreLabel}>Hole</Text></View>
                                                    {Array.from({ length: 9 }, (_, i) => i + 1).map(h => (
                                                        <View key={h} style={styles.scoreCell}><Text style={styles.headerTextBlack}>{h}</Text></View>
                                                    ))}
                                                    <View style={styles.totalCell}><Text style={styles.headerTextBlack}>Total</Text></View>
                                                </View>
                                                <View style={styles.scoreRow}>
                                                    <View style={styles.labelCell}><Text style={styles.scoreLabel}>Best Net</Text></View>
                                                    {item.secondNineNetScores.map((n: number | string, idx: number) => (
                                                        <View key={idx} style={styles.scoreCell}><Text style={styles.scoreText}>{n}</Text></View>
                                                    ))}
                                                    <View style={styles.totalCell}><Text style={styles.totalText}>{item.secondNineTotalNetScore}</Text></View>
                                                </View>
                                            </View>
                                        </ScrollView>
                                    </View>
                                )}
                            </View>
                        )}
                    </View>
                )}
            </View>
        );
    };

    const getTableConfig = (): { headers: string[], data: any[], renderItem: ({ item, index }: { item: any, index: number }) => JSX.Element | null } => {
        if (!results) return { headers: [], data: [], renderItem: () => null };

        switch (results.resultType) {
            case 'TEAM_STANDINGS':
                return {
                    headers: ['#', 'Team', 'Pts', 'Total', 'Behind'],
                    data: results.standings || [],
                    renderItem: renderStandingResult
                };
            case 'SINGLES_MATCH':
                return {
                    headers: ['#', 'Player', 'Match', 'Session', 'Season'],
                    data: results.players || [],
                    renderItem: renderSinglesMatchResult
                };
            case 'SINGLES_STROKE':
                return {
                    headers: ['#', 'Player', 'Gross', 'Net', 'Pts'],
                    data: results.players || [],
                    renderItem: renderSinglesStrokeResult
                };
            case 'TEAM_EVENT':
                return {
                    headers: ['#', 'Team', 'Gross', 'Net', 'Pts'],
                    data: results.teams || [],
                    renderItem: renderTeamEventResult
                };
            default:
                return { headers: [], data: [], renderItem: () => null };
        }
    };

    if (loading) {
        return (
            <View style={styles.centered}>
                <ActivityIndicator size="large" color="#0000ff" />
                <Text style={styles.loadingText}>Loading Results...</Text>
            </View>
        );
    }

    const config = getTableConfig();

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.headerTitle}>{event?.description || 'Event Results'}</Text>
                <Text style={styles.headerSubtitle}>Event #{eventNumber}</Text>
            </View>

            <View style={styles.tableHeader}>
                {config.headers.map((h, i) => (
                    <View key={i} style={i === 1 ? styles.cellName : (i === 0 ? styles.cellRank : styles.cellStat)}>
                        <Text style={styles.headerText}>{h}</Text>
                    </View>
                ))}
            </View>

            <FlatList
                data={config.data}
                keyExtractor={(item, index) => index.toString()}
                renderItem={config.renderItem}
                extraData={expandedTeams}
                contentContainerStyle={styles.listContent}
            />
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f5f5f5',
    },
    centered: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    loadingText: {
        marginTop: 10,
        color: '#666',
    },
    header: {
        backgroundColor: '#fff',
        padding: 15,
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: 'bold',
        color: '#333',
    },
    headerSubtitle: {
        fontSize: 14,
        color: '#666',
        marginTop: 2,
    },
    tableHeader: {
        flexDirection: 'row',
        backgroundColor: '#444',
        paddingVertical: 10,
        paddingHorizontal: 15,
    },
    headerText: {
        color: '#fff',
        fontWeight: 'bold',
        fontSize: 12,
    },
    headerTextBlack: {
        color: '#000',
        fontWeight: 'bold',
        fontSize: 12,
    },
    listContent: {
        paddingBottom: 20,
    },
    rowContainer: {
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
    },
    row: {
        flexDirection: 'row',
        paddingVertical: 12,
        paddingHorizontal: 15,
        alignItems: 'center',
    },
    expandedRow: {
        backgroundColor: '#f9f9f9',
    },
    cellRank: {
        width: 30,
    },
    cellName: {
        flex: 1,
    },
    cellStat: {
        width: 50,
        alignItems: 'flex-end',
    },
    cellPoints: { width: 45, alignItems: 'flex-end' },
    cellTotal: { width: 50, alignItems: 'flex-end' },
    cellBehind: { width: 50, alignItems: 'flex-end' },
    cellText: {
        fontSize: 14,
        color: '#333',
    },
    detailSection: {
        backgroundColor: '#fdfdfd',
        padding: 15,
        borderTopWidth: 1,
        borderTopColor: '#f0f0f0',
    },
    detailHeader: {
        fontSize: 16,
        fontWeight: 'bold',
        marginBottom: 10,
        color: '#444',
    },
    matchDetailItem: {
        marginBottom: 20,
        backgroundColor: '#fff',
        borderRadius: 8,
        padding: 10,
        borderWidth: 1,
        borderColor: '#eee',
    },
    matchHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: 10,
        paddingBottom: 5,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
    },
    matchTitle: {
        fontWeight: 'bold',
        fontSize: 14,
    },
    matchScore: {
        fontWeight: 'bold',
        color: '#007AFF',
    },
    pointBreakdown: {
        backgroundColor: '#f8f9fa',
        padding: 8,
        borderRadius: 4,
        marginBottom: 10,
    },
    breakdownRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: 2,
    },
    breakdownLabel: {
        fontSize: 12,
        color: '#666',
    },
    breakdownValue: {
        fontSize: 12,
        fontWeight: '600',
    },
    scorecardsContainer: {
        marginTop: 10,
    },
    subHeader: {
        fontSize: 14,
        fontWeight: 'bold',
        marginBottom: 8,
        color: '#555',
    },
    playerCard: {
        marginBottom: 15,
        padding: 10,
        backgroundColor: '#fff',
        borderRadius: 6,
        borderWidth: 1,
        borderColor: '#eee',
    },
    playerName: {
        fontWeight: 'bold',
        marginBottom: 8,
        color: '#333',
    },
    scorecardTable: {
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 4,
    },
    scoreRow: {
        flexDirection: 'row',
        borderBottomWidth: 1,
        borderBottomColor: '#ddd',
    },
    labelCell: {
        width: 60,
        padding: 8,
        backgroundColor: '#f0f0f0',
        justifyContent: 'center',
        borderRightWidth: 1,
        borderRightColor: '#ddd',
    },
    scoreCell: {
        width: 35,
        padding: 8,
        justifyContent: 'center',
        alignItems: 'center',
        borderRightWidth: 1,
        borderRightColor: '#ddd',
    },
    totalCell: {
        width: 45,
        padding: 8,
        backgroundColor: '#f0f0f0',
        justifyContent: 'center',
        alignItems: 'center',
    },
    scoreLabel: {
        fontSize: 10,
        fontWeight: 'bold',
    },
    scoreText: {
        fontSize: 11,
    },
    pointHighlight: {
        backgroundColor: '#e6f3ff',
    },
    totalText: {
        fontSize: 11,
        fontWeight: 'bold',
    },
    cardFooter: {
        flexDirection: 'row',
        justifyContent: 'space-around',
        marginTop: 8,
        paddingTop: 8,
        borderTopWidth: 1,
        borderTopColor: '#eee',
    },
    footerStat: {
        fontSize: 12,
        fontWeight: 'bold',
        color: '#555',
    },
    tieBreakerText: {
        fontSize: 10,
        color: '#d9534f',
        fontStyle: 'italic',
        marginTop: 2,
    },
});

export default EventResultsScreen;