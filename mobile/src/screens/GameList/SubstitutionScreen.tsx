import React, {useState, useEffect} from 'react';
import {
    View,
    Text,
    StyleSheet,
    TouchableOpacity,
    FlatList,
    ActivityIndicator,
    Alert,
} from 'react-native';
import {RouteProp, useNavigation, useRoute} from '@react-navigation/native';
import {StackNavigationProp} from '@react-navigation/stack';
import GameService from '../../services/GameService';

type RootStackParamList = {
    Substitution: { gameId: number };
};

type SubstitutionScreenRouteProp = RouteProp<RootStackParamList, 'Substitution'>;

interface PlayerRosterItem {
    id: number;
    name: string;
}

const SubstitutionScreen: React.FC = () => {
    const navigation = useNavigation<StackNavigationProp<any>>();
    const route = useRoute<SubstitutionScreenRouteProp>();
    const {gameId} = route.params;

    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [currentGamePlayers, setCurrentGamePlayers] = useState<PlayerRosterItem[]>([]);
    const [leagueRoster, setLeagueRoster] = useState<PlayerRosterItem[]>([]);
    const [selectedPlayers, setSelectedPlayers] = useState<number[]>([]);
    const [activeIndex, setActiveIndex] = useState<number | null>(null);

    useEffect(() => {
        fetchRoster();
    }, [gameId]);

    const fetchRoster = async () => {
        try {
            setLoading(true);
            const data = await GameService.getRoster(gameId);
            setCurrentGamePlayers(data.currentGamePlayers);
            setLeagueRoster(data.leagueRoster);
            setSelectedPlayers(data.currentGamePlayers.map(p => p.id));
        } catch (error) {
            console.error('Error fetching roster:', error);
            Alert.alert('Error', 'Failed to load player roster.');
        } finally {
            setLoading(false);
        }
    };

    const handleSelectPlayer = (playerId: number) => {
        if (activeIndex === null) return;

        const newSelected = [...selectedPlayers];
        newSelected[activeIndex] = playerId;
        setSelectedPlayers(newSelected);
        setActiveIndex(null);
    };

    const handleSave = async () => {
        try {
            setSaving(true);
            const result = await GameService.substitutePlayers(gameId, selectedPlayers);
            Alert.alert('Success', result.message, [
                {text: 'OK', onPress: () => navigation.goBack()}
            ]);
        } catch (error) {
            console.error('Error substituting players:', error);
            Alert.alert('Error', 'Failed to substitute players.');
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <View style={styles.centered}>
                <ActivityIndicator size="large" color="#007AFF"/>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <Text style={styles.title}>Current Players in Game</Text>
            <View style={styles.currentPlayersContainer}>
                {currentGamePlayers.map((player, index) => {
                    const selectedPlayerId = selectedPlayers[index];
                    const selectedPlayer = leagueRoster.find(p => p.id === selectedPlayerId);
                    const isChanging = activeIndex === index;

                    return (
                        <TouchableOpacity
                            key={index}
                            style={[styles.playerSlot, isChanging && styles.activeSlot]}
                            onPress={() => setActiveIndex(isChanging ? null : index)}
                        >
                            <Text style={styles.slotLabel}>Position {index + 1}:</Text>
                            <Text style={styles.playerName}>
                                {selectedPlayer ? selectedPlayer.name : 'Unassigned'}
                            </Text>
                            <Text style={styles.changeText}>[Tap to Change]</Text>
                        </TouchableOpacity>
                    );
                })}
            </View>

            {activeIndex !== null && (
                <View style={styles.rosterContainer}>
                    <Text style={styles.rosterTitle}>Select Replacement Player:</Text>
                    <FlatList
                        data={leagueRoster}
                        keyExtractor={(item) => item.id.toString()}
                        renderItem={({item}) => (
                            <TouchableOpacity
                                style={styles.rosterItem}
                                onPress={() => handleSelectPlayer(item.id)}
                            >
                                <Text style={styles.rosterItemText}>{item.name}</Text>
                                {selectedPlayers.includes(item.id) && (
                                    <Text style={styles.alreadySelected}>[Selected]</Text>
                                )}
                            </TouchableOpacity>
                        )}
                    />
                </View>
            )}

            <View style={styles.footer}>
                <Text style={styles.warning}>
                    Note: Changing players will reset any scores entered for this game.
                </Text>
                <TouchableOpacity
                    style={[styles.saveButton, saving && styles.disabledButton]}
                    onPress={handleSave}
                    disabled={saving}
                >
                    {saving ? (
                        <ActivityIndicator color="#fff"/>
                    ) : (
                        <Text style={styles.saveButtonText}>Save Substitutions</Text>
                    )}
                </TouchableOpacity>
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    container: {flex: 1, padding: 16, backgroundColor: '#fff'},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    title: {fontSize: 18, fontWeight: 'bold', marginBottom: 16},
    currentPlayersContainer: {marginBottom: 24},
    playerSlot: {
        padding: 12,
        borderWidth: 1,
        borderColor: '#ddd',
        borderRadius: 8,
        marginBottom: 8,
        flexDirection: 'row',
        alignItems: 'center',
    },
    activeSlot: {borderColor: '#007AFF', backgroundColor: '#F0F8FF'},
    slotLabel: {fontWeight: 'bold', marginRight: 8},
    playerName: {flex: 1, fontSize: 16},
    changeText: {fontSize: 12, color: '#007AFF'},
    rosterContainer: {flex: 1, borderTopWidth: 1, borderTopColor: '#eee', paddingTop: 16},
    rosterTitle: {fontSize: 16, fontWeight: 'bold', marginBottom: 8},
    rosterItem: {
        paddingVertical: 12,
        paddingHorizontal: 8,
        borderBottomWidth: 1,
        borderBottomColor: '#f0f0f0',
        flexDirection: 'row',
        justifyContent: 'space-between',
    },
    rosterItemText: {fontSize: 16},
    alreadySelected: {fontSize: 12, color: '#888'},
    footer: {marginTop: 'auto', paddingVertical: 16},
    warning: {color: '#FF3B30', fontSize: 12, textAlign: 'center', marginBottom: 16},
    saveButton: {
        backgroundColor: '#007AFF',
        padding: 16,
        borderRadius: 8,
        alignItems: 'center',
    },
    disabledButton: {backgroundColor: '#A0A0A0'},
    saveButtonText: {color: '#fff', fontSize: 18, fontWeight: 'bold'},
});

export default SubstitutionScreen;