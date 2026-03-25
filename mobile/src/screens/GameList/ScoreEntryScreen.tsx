import React, {useEffect, useState} from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    TextInput,
    TouchableOpacity,
    ActivityIndicator,
    Alert,
    Switch
} from 'react-native';
import {Picker} from '@react-native-picker/picker';
import GameService from '../../services/GameService';
import {ScoreEntryDetails} from '../../types';

const VerticalLabel = ({text, style}: { text: string, style?: any }) => (
    <Text style={[style, {textAlign: 'center', lineHeight: 14, paddingTop: 0, marginTop: 0}]}>
        {text.split('').join('\n')}
    </Text>
);

const ScoreEntryScreen = ({route, navigation}: any) => {
    const {gameId} = route.params;
    const [details, setDetails] = useState<ScoreEntryDetails | null>(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        loadScores();
    }, [gameId]);

    const loadScores = async () => {
        try {
            const data = await GameService.getGameScores(gameId);
            setDetails(data);
            navigation.setOptions({title: data.isRecorded ? 'View Scores' : 'Enter Scores'});
        } catch (error) {
            console.error('Failed to load scores', error);
            Alert.alert('Error', 'Could not load score details for this game.');
        } finally {
            setLoading(false);
        }
    };

    const handleStrokeChange = (playerIndex: number, holeIndex: number, value: string, teamNumber?: 1 | 2) => {
        if (!details) return;

        const newDetails = {...details};
        let strokes: (number | null)[] = [];
        let updateFn: () => void = () => {};

        if (details.type === 'REGULAR') {
            const playerScores = [...(newDetails.playerScores || [])];
            strokes = [...playerScores[playerIndex].strokes];
            updateFn = () => {
                playerScores[playerIndex] = {...playerScores[playerIndex], strokes};
                newDetails.playerScores = playerScores;
            };
        } else if (teamNumber === 1 && newDetails.teamOne) {
            const teamOne = {...newDetails.teamOne};
            const players = [...teamOne.players];
            strokes = [...players[playerIndex].strokes];
            updateFn = () => {
                players[playerIndex] = {...players[playerIndex], strokes};
                teamOne.players = players;
                newDetails.teamOne = teamOne;
            };
        } else if (teamNumber === 2 && newDetails.teamTwo) {
            const teamTwo = {...newDetails.teamTwo};
            const players = [...teamTwo.players];
            strokes = [...players[playerIndex].strokes];
            updateFn = () => {
                players[playerIndex] = {...players[playerIndex], strokes};
                teamTwo.players = players;
                newDetails.teamTwo = teamTwo;
            };
        }

        const cleanValue = value.replace(/[^0-9]/g, '');
        const val = cleanValue === '' ? 0 : parseInt(cleanValue, 10);
        if (!isNaN(val) && val >= 0 && val <= 15) {
            strokes[holeIndex] = val;
            updateFn();
            setDetails(newDetails);
        }
    };

    const handleTeamStrokeChange = (teamNumber: 1 | 2, holeIndex: number, value: string) => {
        if (!details || details.type !== 'TEAM') return;

        const newDetails = {...details};
        const teamKey = teamNumber === 1 ? 'teamOne' : 'teamTwo';
        const team = newDetails[teamKey];
        if (!team) return;

        const teamScore = [...(team.teamScore || [])];
        const cleanValue = value.replace(/[^0-9]/g, '');
        const val = cleanValue === '' ? 0 : parseInt(cleanValue, 10);

        if (!isNaN(val) && val >= 0 && val <= 15) {
            teamScore[holeIndex] = val;
            newDetails[teamKey] = {...team, teamScore};
            setDetails(newDetails);
        }
    };

    const handleTeeChange = (playerIndex: number, teeId: any) => {
        if (!details || details.type !== 'REGULAR') return;
        const newDetails = {...details};
        const playerScores = [...(newDetails.playerScores || [])];
        playerScores[playerIndex] = {
            ...playerScores[playerIndex],
            currentTeeId: parseInt(teeId, 10)
        };
        newDetails.playerScores = playerScores;
        setDetails(newDetails);
    };

    const handlePlayedToggle = (playerIndex: number, value: boolean) => {
        // Method no longer used
    };

    const handleSave = async () => {
        if (!details) return;

        // Validation: Ensure all scores are numbers between 0 and 15
        if (details.type === 'REGULAR' && details.playerScores) {
            for (const ps of details.playerScores) {
                if (ps.strokes.some(s => s === null || isNaN(s as number) || (s as number) < 0 || (s as number) > 15)) {
                    Alert.alert('Validation Error', `Please ensure all scores for ${ps.playerName} are numbers between 0 and 15.`);
                    return;
                }
            }
        }

        setSaving(true);
        try {
            const payload: any = {
                type: details.type,
            };

            if (details.type === 'REGULAR') {
                payload.playerScores = (details.playerScores || []).map(ps => ({
                    playerId: ps.playerId,
                    currentTeeId: ps.currentTeeId,
                    strokes: ps.strokes.map(s => s === null ? 0 : s)
                }));
            } else {
                const allPlayers = [
                    ...(details.teamOne?.players || []),
                    ...(details.teamTwo?.players || [])
                ];
                payload.playerScores = allPlayers.map(p => ({
                    playerId: p.playerId,
                    strokes: p.strokes
                }));
                if (details.isScramble) {
                    payload.teamOneScore = details.teamOne?.teamScore;
                    payload.teamTwoScore = details.teamTwo?.teamScore;
                }
            }

            await GameService.saveGameScores(gameId, payload);
            Alert.alert('Success', 'Scores saved successfully.', [
                {text: 'OK', onPress: () => navigation.goBack()}
            ]);
            navigation.goBack();
        } catch (error) {
            console.error('Failed to save scores', error);
            Alert.alert('Error', 'Failed to save scores. Please try again.');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return <ActivityIndicator style={styles.centered} size="large"/>;
    }

    if (!details) {
        return (
            <View style={styles.centered}>
                <Text>Could not load game details.</Text>
            </View>
        );
    }

    const renderPlayerGrid = (player: any, pIdx: number, teamNumber?: 1 | 2) => {
        // Find the absolute player index if we are in REGULAR mode but called from within a grouped matchup
        let absolutePIdx = pIdx;
        if (details!.type === 'REGULAR' && teamNumber === undefined) {
            // If teamNumber is undefined, pIdx is assumed to be the absolute index in details.playerScores
            absolutePIdx = pIdx;
        } else if (details!.type === 'REGULAR' && teamNumber !== undefined) {
             // If we are in REGULAR mode but rendering within a group, pIdx might be local to that group.
             // However, my proposed update to ScoreEntryScreen will pass the absolute index.
             absolutePIdx = pIdx;
        }

        return (
            <ScrollView horizontal={true} showsHorizontalScrollIndicator={true} persistentScrollbar={true} key={`player-grid-${player.playerId}-${pIdx}`}>
                <View style={styles.scoresGrid}>
                    <View style={styles.gridHeader}>
                        <VerticalLabel text="Hole" style={[styles.gridCell, styles.headerCell, {width: 25}]}/>
                        {details!.nines[0].holes.map((h, hIdx) => (
                            <Text key={`hole-${h.number}-${hIdx}`} style={[styles.gridCell, styles.headerCell, {width: 30}]}>{h.number}</Text>
                        ))}
                        <VerticalLabel text="Total" style={[styles.gridCell, styles.headerCell, {width: 25}]}/>
                    </View>
                    <View style={styles.gridRow}>
                        <VerticalLabel text="Par" style={[styles.gridCell, styles.labelCell, {width: 25}]}/>
                        {details!.nines[0].holes.map((h, hIdx) => (
                            <Text key={`par-${h.number}-${hIdx}`} style={[styles.gridCell, {width: 30}]}>{h.par}</Text>
                        ))}
                        <Text style={[styles.gridCell, {width: 30}]}>{details!.nines[0].holes.reduce((sum: number, h) => sum + h.par, 0)}</Text>
                    </View>
                    <View style={styles.gridRow}>
                        <VerticalLabel text="Score" style={[styles.gridCell, styles.labelCell, {width: 25}]}/>
                        {player.strokes.map((s: number | null, hIdx: number) => (
                            <TextInput
                                key={`score-${hIdx}`}
                                style={[styles.gridCell, styles.scoreInput, {width: 30}]}
                                keyboardType="numeric"
                                value={s === null ? '' : s.toString()}
                                onChangeText={(val) => handleStrokeChange(absolutePIdx, hIdx, val, teamNumber)}
                                maxLength={2}
                            />
                        ))}
                        <Text style={[styles.gridCell, {width: 30}]}>
                            {player.strokes.reduce((sum: number, s: number | null) => sum + (s || 0), 0)}
                        </Text>
                    </View>
                </View>
            </ScrollView>
        );
    };

    const renderTeamScoreGrid = (team: any, teamNumber: 1 | 2) => (
        <ScrollView horizontal={true} showsHorizontalScrollIndicator={true} persistentScrollbar={true} key={`team-score-grid-${teamNumber}`}>
            <View style={styles.scoresGrid}>
                <View style={styles.gridHeader}>
                    <VerticalLabel text="Hole" style={[styles.gridCell, styles.headerCell, {width: 25}]}/>
                    {details!.nines[0].holes.map((h, hIdx) => (
                        <Text key={`hole-t-${h.number}-${hIdx}`} style={[styles.gridCell, styles.headerCell, {width: 30}]}>{h.number}</Text>
                    ))}
                    <VerticalLabel text="Total" style={[styles.gridCell, styles.headerCell, {width: 25}]}/>
                </View>
                <View style={styles.gridRow}>
                    <VerticalLabel text="Par" style={[styles.gridCell, styles.labelCell, {width: 25}]}/>
                    {details!.nines[0].holes.map((h, hIdx) => (
                        <Text key={`par-t-${h.number}-${hIdx}`} style={[styles.gridCell, {width: 30}]}>{h.par}</Text>
                    ))}
                    <Text style={[styles.gridCell, {width: 30}]}>{details!.nines[0].holes.reduce((sum: number, h) => sum + h.par, 0)}</Text>
                </View>
                <View style={styles.gridRow}>
                    <VerticalLabel text="Team" style={[styles.gridCell, styles.labelCell, {width: 25}]}/>
                    {(team.teamScore || []).map((s: number | null, hIdx: number) => (
                        <TextInput
                            key={`team-score-${hIdx}`}
                            style={[styles.gridCell, styles.scoreInput, {width: 30}]}
                            keyboardType="numeric"
                            value={s === null ? '' : s.toString()}
                            onChangeText={(val) => handleTeamStrokeChange(teamNumber, hIdx, val)}
                            maxLength={2}
                        />
                    ))}
                    <Text style={[styles.gridCell, {width: 30}]}>
                        {(team.teamScore || []).reduce((sum: number, s: number | null) => sum + (s || 0), 0)}
                    </Text>
                </View>
            </View>
        </ScrollView>
    );

    const renderRegularScores = () => {
        if (!details || !details.playerScores) return null;

        // Group players by matchId
        const matchups: { [key: number]: { absoluteIndex: number, player: any }[] } = {};
        const individualPlayers: { absoluteIndex: number, player: any }[] = [];

        details.playerScores.forEach((player, index) => {
            if (player.matchId) {
                if (!matchups[player.matchId]) matchups[player.matchId] = [];
                matchups[player.matchId].push({absoluteIndex: index, player});
            } else {
                individualPlayers.push({absoluteIndex: index, player});
            }
        });

        const renderPlayerCard = (player: any, absoluteIndex: number, isNested = false) => (
            <View key={`player-${player.playerId}-${absoluteIndex}`} style={isNested ? styles.nestedPlayerRow : styles.playerCard}>
                <View style={styles.playerHeader}>
                    <View style={{flexDirection: 'column'}}>
                        <Text style={isNested ? styles.nestedPlayerName : styles.playerName}>{player.playerName}</Text>
                        {player.isDuplicate && (
                            <Text style={styles.duplicateIndicator}>Substituted Score</Text>
                        )}
                    </View>
                </View>

                <View style={styles.teeContainer}>
                    <Text style={styles.label}>Tee:</Text>
                    <Picker
                        selectedValue={player.currentTeeId}
                        onValueChange={(val) => handleTeeChange(absoluteIndex, val)}
                        style={styles.picker}
                    >
                        {player.availableTees.map((tee: any, tIdx: number) => (
                            <Picker.Item key={`tee-${tee.id}-${tIdx}`} label={tee.name} value={tee.id.toString()}/>
                        ))}
                    </Picker>
                </View>
                {renderPlayerGrid(player, absoluteIndex)}
            </View>
        );

        return (
            <>
                {Object.keys(matchups).map((matchId: any) => {
                    const players = matchups[matchId];
                    return (
                        <View key={`matchup-${matchId}`} style={styles.matchupContainer}>
                            <Text style={styles.matchupHeader}>
                                {players.map(p => p.player.playerName).join(' vs ')}
                            </Text>
                            {players.map(p => renderPlayerCard(p.player, p.absoluteIndex, true))}
                        </View>
                    );
                })}
                {individualPlayers.map(p => renderRegularPlayerCard(p.player, p.absoluteIndex))}
            </>
        );
    };

    // Helper for non-matchup players (kept for compatibility if needed)
    const renderRegularPlayerCard = (player: any, pIdx: number) => (
        <View key={`player-${player.playerId}-${pIdx}`} style={styles.playerCard}>
            <View style={styles.playerHeader}>
                <View style={{flexDirection: 'column'}}>
                    <Text style={styles.playerName}>{player.playerName}</Text>
                    {player.isDuplicate && (
                        <Text style={styles.duplicateIndicator}>Substituted Score</Text>
                    )}
                </View>
            </View>

            <View style={styles.teeContainer}>
                <Text style={styles.label}>Tee:</Text>
                <Picker
                    selectedValue={player.currentTeeId}
                    onValueChange={(val) => handleTeeChange(pIdx, val)}
                    style={styles.picker}
                >
                    {player.availableTees.map((tee: any, tIdx: number) => (
                        <Picker.Item key={`tee-${tee.id}-${tIdx}`} label={tee.name} value={tee.id.toString()}/>
                    ))}
                </Picker>
            </View>
            {renderPlayerGrid(player, pIdx)}
        </View>
    );

    return (
        <ScrollView style={styles.container}>
            {details.type === 'REGULAR' ? renderRegularScores() : (
                <>
                    {[details.teamOne, details.teamTwo].map((team, tIdx) => {
                        if (!team) return null;
                        const teamNum = (tIdx + 1) as 1 | 2;
                        return (
                            <View key={`team-section-${tIdx}`} style={styles.playerCard}>
                                <View style={styles.playerHeader}>
                                    <Text style={styles.playerName}>{team.name || `Team ${teamNum}`}</Text>
                                </View>
                                
                                {details.isScramble ? (
                                    <>
                                        {renderTeamScoreGrid(team, teamNum)}
                                        <View style={styles.playerRoster}>
                                            <Text style={styles.rosterTitle}>Roster:</Text>
                                            <Text style={styles.rosterText}>{team.players.map(p => p.playerName).join(', ')}</Text>
                                        </View>
                                    </>
                                ) : (
                                    team.players.map((p, pIdx) => (
                                        <View key={`team-player-${p.playerId}-${pIdx}`} style={styles.nestedPlayerRow}>
                                            <Text style={styles.nestedPlayerName}>{p.playerName}</Text>
                                            {renderPlayerGrid(p, pIdx, teamNum)}
                                        </View>
                                    ))
                                )}
                            </View>
                        );
                    })}
                </>
            )}

            <TouchableOpacity
                style={[styles.saveButton, saving && styles.disabledButton]}
                onPress={handleSave}
                disabled={saving}
            >
                {saving ? (
                    <ActivityIndicator color="#fff"/>
                ) : (
                    <Text style={styles.saveButtonText}>Save Scores</Text>
                )}
            </TouchableOpacity>
            <View style={{height: 40}}/>
        </ScrollView>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#f5f5f5', padding: 10},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    playerCard: {
        backgroundColor: '#fff',
        borderRadius: 10,
        padding: 15,
        marginBottom: 20,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: {width: 0, height: 1},
        shadowOpacity: 0.2,
        shadowRadius: 2,
    },
    playerHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 10,
        borderBottomWidth: 1,
        borderBottomColor: '#eee',
        paddingBottom: 5,
    },
    playerName: {fontSize: 18, fontWeight: 'bold'},
    playedToggle: {flexDirection: 'row', alignItems: 'center'},
    playedLabel: {marginRight: 5, fontSize: 12, color: '#666'},
    teeContainer: {flexDirection: 'row', alignItems: 'center', marginBottom: 15, width: '100%'},
    label: {fontSize: 14, fontWeight: '600', marginRight: 10},
    picker: {flex: 1, height: 50, maxWidth: 330},
    scoresGrid: {borderWidth: 1, borderColor: '#ccc', alignSelf: 'flex-start'},
    gridHeader: {flexDirection: 'row', backgroundColor: '#f0f0f0', alignItems: 'flex-start'},
    gridRow: {flexDirection: 'row', borderTopWidth: 1, borderTopColor: '#ccc', alignItems: 'flex-start'},
    gridCell: {
        paddingTop: 0,
        paddingBottom: 5,
        paddingHorizontal: 1,
        textAlign: 'center',
        textAlignVertical: 'top',
        borderRightWidth: 1,
        borderRightColor: '#ccc',
        fontSize: 12,
        width: 30,
        alignSelf: 'flex-start',
    },
    headerCell: {
        fontWeight: 'bold',
        width: 30,
    },
    labelCell: {
        backgroundColor: '#f9f9f9',
        fontWeight: '600',
        width: 25,
        textAlign: 'center',
    },
    scoreInput: {
        backgroundColor: '#fff',
        color: '#007AFF',
        fontWeight: 'bold',
        padding: 0,
        margin: 0,
    },
    duplicateIndicator: {
        fontSize: 12,
        color: '#FF9500',
        fontWeight: 'bold',
        marginTop: 2,
    },
    saveButton: {
        backgroundColor: '#34C759',
        padding: 15,
        borderRadius: 8,
        alignItems: 'center',
        marginBottom: 20,
    },
    disabledButton: {backgroundColor: '#ccc'},
    saveButtonText: {color: '#fff', fontSize: 18, fontWeight: 'bold'},
    matchupContainer: {
        backgroundColor: '#fff',
        borderRadius: 10,
        padding: 15,
        marginBottom: 20,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: {width: 0, height: 1},
        shadowOpacity: 0.2,
        shadowRadius: 2,
    },
    matchupHeader: {
        fontSize: 16,
        fontWeight: 'bold',
        color: '#007AFF',
        borderBottomWidth: 1,
        borderBottomColor: '#007AFF',
        paddingBottom: 5,
        marginBottom: 5,
    },
    nestedPlayerRow: {
        marginTop: 15,
        borderTopWidth: 1,
        borderTopColor: '#f0f0f0',
        paddingTop: 10,
    },
    nestedPlayerName: {
        fontSize: 14,
        fontWeight: '600',
        marginBottom: 5,
        color: '#444',
    },
    playerRoster: {
        marginTop: 10,
        flexDirection: 'row',
        flexWrap: 'wrap',
    },
    rosterTitle: {
        fontSize: 12,
        fontWeight: 'bold',
        color: '#666',
        marginRight: 5,
    },
    rosterText: {
        fontSize: 12,
        color: '#666',
    },
});

export default ScoreEntryScreen;
